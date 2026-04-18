<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Tenant scoping: every user belongs to one Structure.
            // Nullable for the bootstrap case (very first super-admin user
            // before any structure exists). Production users always have one.
            $table->uuid('structure_id')->nullable()->index();

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->string('employee_number')->nullable()->index();

            // Persona type — one of: intervenant, coordinateur, dirigeant,
            // referent_qualite, rh, beneficiaire_portal. Values kept in
            // French as they are the canonical persona identifiers used by
            // Spatie Permission roles and sector documentation.
            $table->string('type', 30)->nullable();

            $table->string('specialty')->nullable();
            $table->date('hired_at')->nullable();
            $table->string('status', 20)->default('active');

            // Fortify two-factor authentication columns (auto-encrypted
            // by Fortify's TwoFactorAuthenticatable trait).
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            // RGPD Art 17 erasure flag. Set when the user's personal data
            // has been anonymized but the row is retained for audit
            // evidence. Distinct from deleted_at (ordinary soft delete).
            $table->timestamp('erased_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
