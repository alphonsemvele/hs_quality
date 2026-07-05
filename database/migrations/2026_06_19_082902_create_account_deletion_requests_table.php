<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GDPR (article 17) account deletion request — created when a user opts
 * into erasure. A 30-day cooling-off period (effective_at) gives the
 * user time to cancel before anonymization actually fires; the
 * scheduled ProcessAccountDeletionRequestsJob picks up entries past
 * their effective_at and runs the AnonymizeUserAccountService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletion_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('structure_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 20)->default('pending');
            $table->timestamp('requested_at');
            $table->timestamp('effective_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('failure_reason', 512)->nullable();
            $table->timestamps();

            $table->foreign('structure_id')->references('id')->on('structures')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['status', 'effective_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
    }
};
