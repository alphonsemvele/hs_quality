<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cotation HAS A/B/C/D/NA sur les réponses d'audit. Pour les items
 * dont scale = has_cotation, on stocke à la fois :
 *   - cotation (lettre A/B/C/D/NA) — utilisée par l'UI et la
 *     génération du PAC
 *   - score (numérique) — calculé depuis la cotation par le contrôleur
 *     pour conserver les agrégations SQL (SUM/AVG sur score)
 *
 * Pour NA, score reste NULL et l'item est exclu du calcul de la note
 * (numérateur ET dénominateur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_run_responses', function (Blueprint $table): void {
            $table->string('cotation', 2)->nullable()->after('score');
            $table->index(['structure_id', 'audit_run_id', 'cotation'], 'audit_responses_struct_run_cotation_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_run_responses', function (Blueprint $table): void {
            $table->dropIndex('audit_responses_struct_run_cotation_idx');
            $table->dropColumn('cotation');
        });
    }
};
