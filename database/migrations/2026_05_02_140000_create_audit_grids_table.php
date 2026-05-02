<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit grid template — reusable container of scoreable items.
 * Spec: PHASE2_PROGRESS.md M6.1 + IMPLEMENTATION_PLAN line 440.
 *
 * Per-tenant by design: each structure gets a seeded copy of the
 * HAS / ISO 9001 / AFNOR NF X50-056 reference grids so they can
 * customize without affecting other tenants. Custom grids are
 * authored from scratch with `source = custom`.
 *
 * `weight_scheme` is a JSON object — for now {"type":"equal"} is
 * the default; future schemes can add weights per section without
 * a migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_grids', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // AuditGridSource enum (string for forward-compat).
            $table->string('source')->default('custom');

            $table->jsonb('weight_scheme')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'is_active']);
            $table->index(['structure_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_grids');
    }
};
