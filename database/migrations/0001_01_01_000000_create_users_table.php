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

            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('telephone')->nullable();
            $table->string('avatar')->nullable();
            $table->string('matricule')->nullable()->index();

            // Persona type — one of: intervenant, coordinateur, dirigeant,
            // referent_qualite, rh, beneficiaire_portal.
            // Spatie Permission's role table is the authoritative source for
            // "what this user can do"; `type` is a denormalized column for
            // quick filtering in dashboards and queries.
            $table->string('type', 30)->nullable();

            $table->string('specialite')->nullable();
            $table->date('date_embauche')->nullable();
            $table->string('statut', 20)->default('actif');

            // Fortify two-factor authentication columns
            // Both encrypted at the model level via casts().
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            // RGPD Art 17 right-to-erasure flag. Set when the user's personal
            // data has been anonymized but the row is retained for audit
            // evidence. Different from deleted_at (ordinary soft delete).
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
