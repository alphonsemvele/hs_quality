<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Priorité auto sur PAC actions, dérivée du couple (niveau exigence,
 * cotation) du Excel `grille_evaluation_SAP` onglet "Plan d'action" :
 *   - critique : Impératif HAS + cotation C ou D
 *   - elevee   : cotation D (hors impératif)
 *   - moyenne  : cotation C (hors impératif)
 *   - normale  : autres cas
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pac_actions', function (Blueprint $table): void {
            $table->string('priority', 16)->nullable()->after('status');
            $table->index(['structure_id', 'pac_id', 'priority'], 'pac_actions_struct_pac_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pac_actions', function (Blueprint $table): void {
            $table->dropIndex('pac_actions_struct_pac_priority_idx');
            $table->dropColumn('priority');
        });
    }
};
