<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records the real super-admin actor behind any tenant-scoped audit row
 * written while a "view as dirigeant" impersonation session is active.
 *
 * Without this column, audit_user_id alone names whoever was logged in —
 * which is the super-admin themselves. The column makes the impersonation
 * context explicit and queryable: "show every action performed under
 * impersonation against structure X". CDC / RGPD / HDS expect this trace
 * to exist whenever a platform operator touches tenant data.
 */
return new class extends Migration
{
    public function up(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->table($table, function (Blueprint $table): void {
            $table->unsignedBigInteger('impersonator_id')->nullable()->after('user_id');

            $table->index(['impersonator_id', 'created_at'], 'audits_impersonator_id_created_at_index');
        });
    }

    public function down(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->table($table, function (Blueprint $table): void {
            $table->dropIndex('audits_impersonator_id_created_at_index');
            $table->dropColumn('impersonator_id');
        });
    }
};
