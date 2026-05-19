<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Nullable FK — only set for UserType::BeneficiairePortal accounts.
            // Links the portal user to their Beneficiary record so the portal
            // can load their own data without going through the tenant resolver.
            $table->foreignUuid('beneficiary_id')
                ->nullable()
                ->after('structure_id')
                ->constrained('beneficiaries')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['beneficiary_id']);
            $table->dropColumn('beneficiary_id');
        });
    }
};
