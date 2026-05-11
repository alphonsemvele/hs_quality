<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cashier customer columns — added to `structures` rather than the
 * default `users` because the billable entity in QualitéDomicile is
 * the structure (per-seat subscription owned at the tenant level,
 * not the individual user). Spec: PHASE2_PROGRESS.md C1.
 *
 * Cashier is configured to use Structure as the customer model in
 * AppServiceProvider via Cashier::useCustomerModel().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('structures', function (Blueprint $table) {
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('structures', function (Blueprint $table) {
            $table->dropIndex(['stripe_id']);
            $table->dropColumn([
                'stripe_id',
                'pm_type',
                'pm_last_four',
                'trial_ends_at',
            ]);
        });
    }
};
