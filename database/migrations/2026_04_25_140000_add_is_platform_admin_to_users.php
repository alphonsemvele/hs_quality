<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `is_platform_admin` flags users on the platform-operator surface
 * (provisioning structures, suspending tenants, billing later). It is
 * orthogonal to Spatie roles, which are tenant-scoped via team_id and
 * therefore can't model a "no tenant" assignment without changes to
 * the model_has_roles primary key.
 *
 * The flag lives on User instead of via a "global role" because:
 *   1. There's exactly one platform-admin permission today (manage tenants).
 *   2. Spatie's team-aware schema requires team_foreign_key in the
 *      composite PK, blocking NULL.
 *   3. Boolean checks on the User are O(1) and need no team-context dance.
 *
 * If the operator surface ever grows multiple distinct platform roles
 * (billing admin, support agent, etc), migrate to a `platform_role` enum
 * column on users. Don't reintroduce Spatie at the global scope.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_platform_admin')->default(false)->after('status');
            $table->index('is_platform_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['is_platform_admin']);
            $table->dropColumn('is_platform_admin');
        });
    }
};
