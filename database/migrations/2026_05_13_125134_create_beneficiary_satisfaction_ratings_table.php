<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Beneficiary satisfaction ratings — Phase 3 satisfaction module groundwork.
 *
 * Captures a manual satisfaction record (1-5 stars + free-text comment)
 * tied to a beneficiary. Optionally linked to an intervention so the
 * rating can be traced back to a specific visit. The "comment" column is
 * encrypted at the model layer because it may reveal sensitive context
 * about the beneficiary's state.
 *
 * Cross-tenant boundary enforced by structure_id + BelongsToStructure
 * global scope on the model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_satisfaction_ratings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('structure_id');
            $table->uuid('beneficiary_id');
            $table->uuid('intervention_id')->nullable();
            $table->unsignedSmallInteger('score'); // 1..5
            $table->text('comment')->nullable(); // encrypted at model layer
            $table->date('rated_at');
            $table->foreignId('rated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('structure_id')->references('id')->on('structures')->cascadeOnDelete();
            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->cascadeOnDelete();
            $table->foreign('intervention_id')->references('id')->on('interventions')->nullOnDelete();

            $table->index(['structure_id', 'beneficiary_id', 'rated_at']);
            $table->index(['structure_id', 'rated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_satisfaction_ratings');
    }
};
