<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IntervenantAssignment — records that one intervenant (a user with
 * type=intervenant) is or was assigned to care for one beneficiary.
 *
 * Modeled as a first-class row (not just a many-to-many pivot) because
 * we need to preserve HISTORY for regulatory traceability: who cared for
 * whom, from when to when, and why the assignment changed. Currently-active
 * assignments are those with unassigned_at IS NULL.
 *
 * Tenant scoping: structure_id is denormalized from the user + beneficiary
 * (both must share the same structure). A database-level CHECK constraint
 * would be possible but costly; we enforce at the service + test layers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervenant_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('beneficiary_id')
                ->constrained('beneficiaries')
                ->cascadeOnDelete();

            $table->foreignId('assigned_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // One active assignment per (intervenant, beneficiary) — enforced
            // by a partial unique index on rows where unassigned_at IS NULL.
            // Historical rows (unassigned_at filled) don't participate so
            // re-assignment after un-assigning is allowed.
            $table->unique(
                ['user_id', 'beneficiary_id', 'unassigned_at'],
                'intervenant_assignments_active_unique',
            );

            $table->index(['structure_id', 'user_id']);
            $table->index(['structure_id', 'beneficiary_id']);
            $table->index(['user_id', 'unassigned_at']);
            $table->index(['beneficiary_id', 'unassigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervenant_assignments');
    }
};
