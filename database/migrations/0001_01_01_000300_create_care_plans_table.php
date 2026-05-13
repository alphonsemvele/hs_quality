<?php

use App\Models\Beneficiary;
use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Care plans (plans d'accompagnement) — one beneficiary may have at most one
 * `active` plan at a time, plus any number of archived versions for history.
 * The `objectives` text column may contain health-related context, so it's
 * encrypted at the model level.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('beneficiary_id')
                ->constrained('beneficiaries')
                ->cascadeOnDelete();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title');
            $table->text('objectives')->nullable();      // encrypted at model level
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->string('status', 20)->default('draft');
            $table->timestamp('archived_at')->nullable();
            $table->text('archived_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'beneficiary_id', 'status']);
            $table->index(['structure_id', 'status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_plans');
    }
};
