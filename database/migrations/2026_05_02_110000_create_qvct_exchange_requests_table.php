<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QVCT exchange request — an intervenant asks to talk to their manager
 * or référent RH. Spec: CDC §M3 line "Secure exchange request tool
 * with manager or référent RH" + PHASE2_PROGRESS.md M3.24.
 *
 * Privacy: requester's identity is intentionally surfaced (the addressee
 * has to know who asked to talk — anonymous-talk-requests are not the
 * spec). The `message` text is encrypted at rest because it may include
 * mental-health context similar to journal entries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qvct_exchange_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('requester_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // QvctExchangeAddresseeRole enum.
            $table->string('addressee_role');

            // QvctExchangeStatus enum.
            $table->string('status')->default('pending');

            // Encrypted free-text — short context the requester wants to share
            // before the meeting. Optional.
            $table->text('message')->nullable();

            $table->foreignId('accepted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();

            $table->timestamp('scheduled_at')->nullable();

            $table->timestamp('closed_at')->nullable();
            $table->string('closed_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'status', 'addressee_role']);
            $table->index(['structure_id', 'requester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qvct_exchange_requests');
    }
};
