<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Enable PostgreSQL trigram extension and add GIN indexes on the columns
 * scanned by the Cmd+K quick search. Trigrams give us:
 *   - Fuzzy matching (LIKE/ILIKE only matches exact substrings)
 *   - Similarity ranking (closer matches first)
 *   - Index-backed wildcard searches even with %text% on both sides
 *
 * Skipped silently on non-Postgres drivers (test SQLite, dev SQLite). On
 * those, SearchController already falls back to LIKE — accuracy is lower
 * but functional.
 *
 * Each index creation is wrapped: if `pg_trgm` couldn't be installed on the
 * cluster (typical of shared cPanel hosts that ship Postgres without the
 * `postgresql-contrib` package), the migration succeeds anyway. The
 * SearchController fallback path keeps the feature usable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        } catch (Throwable $e) {
            Log::warning('pg_trgm extension unavailable — Cmd+K search will use LIKE fallback only.', [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (Schema::hasTable('beneficiaries')) {
            $this->safeIndex('beneficiaries_first_name_trgm_idx', 'beneficiaries', 'first_name');
            $this->safeIndex('beneficiaries_last_name_trgm_idx', 'beneficiaries', 'last_name');
        }

        if (Schema::hasTable('audit_runs')) {
            $this->safeIndex('audit_runs_title_trgm_idx', 'audit_runs', 'title');
        }

        if (Schema::hasTable('plans_amelioration') && Schema::hasColumn('plans_amelioration', 'titre')) {
            $this->safeIndex('plans_amelioration_titre_trgm_idx', 'plans_amelioration', 'titre');
        }

        if (Schema::hasTable('users')) {
            $this->safeIndex('users_first_name_trgm_idx', 'users', 'first_name');
            $this->safeIndex('users_last_name_trgm_idx', 'users', 'last_name');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ([
            'beneficiaries_first_name_trgm_idx',
            'beneficiaries_last_name_trgm_idx',
            'audit_runs_title_trgm_idx',
            'plans_amelioration_titre_trgm_idx',
            'plans_amelioration_title_trgm_idx',
            'users_first_name_trgm_idx',
            'users_last_name_trgm_idx',
        ] as $index) {
            try {
                DB::statement('DROP INDEX IF EXISTS '.$index);
            } catch (Throwable) {
                // Best-effort.
            }
        }

        // Deliberately not dropping the extension — other parts of the
        // schema may end up depending on it.
    }

    private function safeIndex(string $name, string $table, string $column): void
    {
        try {
            DB::statement(sprintf(
                'CREATE INDEX IF NOT EXISTS %s ON %s USING gin (%s gin_trgm_ops)',
                $name,
                $table,
                $column,
            ));
        } catch (Throwable $e) {
            Log::warning('Trigram index creation failed, continuing.', [
                'index' => $name,
                'table' => $table,
                'column' => $column,
                'error' => $e->getMessage(),
            ]);
        }
    }
};
