<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for owen-it/laravel-auditing, extended with structure_id so
 * audit entries are themselves tenant-scoped (a user from structure A sees
 * only their structure's audit logs, never structure B's).
 *
 * See: references/audit-logging/package-setup.md
 *      references/audit-logging/tenant-scoped-driver.md
 */
return new class extends Migration
{
    public function up(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $morphPrefix = config('audit.user.morph_prefix', 'user');

            $table->bigIncrements('id');

            // Tenant scoping — nullable because some audit events happen outside
            // a tenant context (super-admin actions, scheduled aggregations,
            // cross-tenant benchmark queries).
            $table->uuid('structure_id')->nullable()->index();

            $table->string($morphPrefix.'_type')->nullable();
            $table->unsignedBigInteger($morphPrefix.'_id')->nullable();
            $table->string('event');
            // Wave 1 / C4 — domain models on this project use UUID primary
            // keys (HasUuids trait). Default $table->morphs() creates an
            // unsigned bigint auditable_id, which silently rejects UUID
            // inserts — owen-it swallows the exception and no audit row is
            // ever written. Use string + manual indexes instead so UUID and
            // bigint primary keys both round-trip correctly.
            $table->string('auditable_type');
            $table->string('auditable_id');
            $table->index(['auditable_type', 'auditable_id'], 'audits_auditable_type_id_index');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();

            $table->index([$morphPrefix.'_id', $morphPrefix.'_type']);
            $table->index(['structure_id', 'created_at']);
        });
    }

    public function down(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->drop($table);
    }
};
