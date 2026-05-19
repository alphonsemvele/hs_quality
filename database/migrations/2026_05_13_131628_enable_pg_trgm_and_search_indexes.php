<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        if (Schema::hasTable('beneficiaries')) {
            DB::statement('CREATE INDEX IF NOT EXISTS beneficiaries_first_name_trgm_idx ON beneficiaries USING gin (first_name gin_trgm_ops)');
            DB::statement('CREATE INDEX IF NOT EXISTS beneficiaries_last_name_trgm_idx ON beneficiaries USING gin (last_name gin_trgm_ops)');
        }

        if (Schema::hasTable('audit_runs')) {
            DB::statement('CREATE INDEX IF NOT EXISTS audit_runs_title_trgm_idx ON audit_runs USING gin (title gin_trgm_ops)');
        }

        if (Schema::hasTable('plans_amelioration') && Schema::hasColumn('plans_amelioration', 'titre')) {
            DB::statement('CREATE INDEX IF NOT EXISTS plans_amelioration_titre_trgm_idx ON plans_amelioration USING gin (titre gin_trgm_ops)');
        }

        if (Schema::hasTable('users')) {
            DB::statement('CREATE INDEX IF NOT EXISTS users_first_name_trgm_idx ON users USING gin (first_name gin_trgm_ops)');
            DB::statement('CREATE INDEX IF NOT EXISTS users_last_name_trgm_idx ON users USING gin (last_name gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS beneficiaries_first_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS beneficiaries_last_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS audit_runs_title_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS plans_amelioration_titre_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS plans_amelioration_title_trgm_idx'); // legacy name
        DB::statement('DROP INDEX IF EXISTS users_first_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS users_last_name_trgm_idx');

        // Deliberately not dropping the extension — other parts of the
        // schema may end up depending on it.
    }
};
