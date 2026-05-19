<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3 — partial indexes that support partition-like pruning on the three
 * highest-volume tables: interventions, qvct_responses, audit_logs.
 *
 * PostgreSQL range partitioning (PARTITION BY RANGE) requires a superuser DDL
 * change that cannot be applied to an existing table without a full table
 * rewrite. We defer that to Phase 4 (dedicated maintenance window).
 * These BRIN indexes on the time column give the query planner equivalent
 * scan pruning on append-only time-series data at a fraction of the size of
 * a B-tree index — appropriate for Phase 3 scale (200 structures).
 *
 * BRIN (Block Range INdex): stores only the min/max value per 128-page block.
 * Effective when rows are inserted roughly in time order (true here — both
 * tables are insert-only with timestamps assigned at write time).
 */
return new class extends Migration
{
    public function up(): void
    {
        // BRIN indexes are PostgreSQL-only. In SQLite (tests) skip silently.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // CONCURRENTLY cannot run inside a transaction. Laravel wraps migrations
        // in transactions by default, so we use regular (non-concurrent) creation
        // here. In production, run these statements manually outside a transaction
        // if zero-downtime index creation is required.
        DB::statement(
            'CREATE INDEX IF NOT EXISTS
             idx_interventions_planned_date_brin
             ON interventions USING BRIN (planned_date)
             WITH (pages_per_range = 128)',
        );

        // qvct_responses uses submitted_at (timestamps = false on that model).
        if (Schema::hasTable('qvct_responses') && Schema::hasColumn('qvct_responses', 'submitted_at')) {
            DB::statement(
                'CREATE INDEX IF NOT EXISTS
                 idx_qvct_responses_submitted_at_brin
                 ON qvct_responses USING BRIN (submitted_at)
                 WITH (pages_per_range = 128)',
            );
        }

        // actual table is quality_audits, not audits.
        if (Schema::hasTable('quality_audits')) {
            DB::statement(
                'CREATE INDEX IF NOT EXISTS
                 idx_quality_audits_created_at_brin
                 ON quality_audits USING BRIN (created_at)
                 WITH (pages_per_range = 128)',
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_interventions_planned_date_brin');
        DB::statement('DROP INDEX IF EXISTS idx_qvct_responses_created_at_brin');
        DB::statement('DROP INDEX IF EXISTS idx_audits_created_at_brin');
    }
};
