<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `billing_email` on structures — Cashier's customerEmail() accessor
 * reads this. Distinct from any individual user's email so billing
 * receipts go to a stable inbox (typically AP / facturation@) even
 * when individual users join/leave the structure.
 *
 * Spec: PHASE2_PROGRESS.md C1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('structures', function (Blueprint $table): void {
            $table->string('billing_email')->nullable()->after('siret');
        });
    }

    public function down(): void
    {
        Schema::table('structures', function (Blueprint $table): void {
            $table->dropColumn('billing_email');
        });
    }
};
