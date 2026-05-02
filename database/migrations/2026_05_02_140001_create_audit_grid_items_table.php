<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Individual scoreable item inside an audit grid. Spec:
 * PHASE2_PROGRESS.md M6.2 + IMPLEMENTATION_PLAN line "individual
 * scoreable items (label, evidence required, scale)".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_grid_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('audit_grid_id')
                ->constrained('audit_grids')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // AuditItemScale enum.
            $table->string('scale')->default('binary');

            // Weight for this item — defaults to scale's max_score so a
            // simple grid sums responses naively. Higher weights tilt
            // scoring toward critical items.
            $table->decimal('max_points', 6, 2)->default(1);

            $table->boolean('evidence_required')->default(false);
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['structure_id', 'audit_grid_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_grid_items');
    }
};
