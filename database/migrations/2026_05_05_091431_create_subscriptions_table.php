<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cashier subscriptions table. Owner FK is `structure_id` (UUID, not
 * the default `bigint user_id`) because subscriptions belong to the
 * tenant, not the individual user. The `type` column is Cashier's
 * subscription label — we use a single "default" subscription per
 * structure in the first iteration.
 *
 * Spec: PHASE2_PROGRESS.md C1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('structure_id');
            $table->string('type');
            $table->string('stripe_id')->unique();
            $table->string('stripe_status');
            $table->string('stripe_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['structure_id', 'stripe_status']);
            $table->foreign('structure_id')
                ->references('id')->on('structures')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
