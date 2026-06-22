<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Axes thématiques d'une grille d'audit. Introduit avec la grille
 * unifiée SAP (HAS + AFNOR + Cap'Handéo, 10 axes / 75 exigences).
 * Une grille peut exister sans axes (rétrocompatible : audit_grid_items
 * existants ont axis_id NULL et restent affichés à plat).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_grid_axes', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('audit_grid_id')
                ->constrained('audit_grids')
                ->cascadeOnDelete();

            $table->string('code', 16);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['structure_id', 'audit_grid_id', 'position']);
            $table->unique(['audit_grid_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_grid_axes');
    }
};
