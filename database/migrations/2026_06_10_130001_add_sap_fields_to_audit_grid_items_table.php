<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Champs spécifiques à la grille unifiée SAP sur les items :
 *   - axis_id : axe thématique parent (10 axes pour SAP, NULL pour
 *     les grilles plates ISO / AFNOR / Custom).
 *   - sources : référentiels d'origine de l'exigence (HAS, AFNOR,
 *     CAP_HANDEO) — tableau JSONB car une exigence peut croiser
 *     plusieurs sources à la fois.
 *   - level : niveau d'exigence S / I / + (Standard, Impératif HAS,
 *     Supra-réglementaire Cap'Handéo).
 *
 * Tous nullable : rétrocompatible avec les grilles existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_grid_items', function (Blueprint $table): void {
            $table->foreignUuid('axis_id')
                ->nullable()
                ->after('audit_grid_id')
                ->constrained('audit_grid_axes')
                ->nullOnDelete();

            $table->jsonb('sources')->nullable()->after('description');
            $table->string('level', 4)->nullable()->after('sources');

            $table->index(['structure_id', 'audit_grid_id', 'axis_id', 'position'], 'audit_items_struct_grid_axis_pos_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_grid_items', function (Blueprint $table): void {
            $table->dropIndex('audit_items_struct_grid_axis_pos_idx');
            $table->dropConstrainedForeignId('axis_id');
            $table->dropColumn(['sources', 'level']);
        });
    }
};
