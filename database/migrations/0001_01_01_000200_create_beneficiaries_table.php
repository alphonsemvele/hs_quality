<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Beneficiaries — the care recipients. Each belongs to exactly one Structure
 * (row-level tenancy). Encrypted columns store RGPD Art 9 special-category
 * health data (medical notes, allergies, medical history, current treatments)
 * — see references/compliance/encrypted-fields.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // --- Identity ---------------------------------------------------
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 1)->nullable();

            // --- Contact ----------------------------------------------------
            $table->text('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();

            // --- Social ----------------------------------------------------
            $table->string('marital_status', 50)->nullable();

            // --- Dependency grading (GIR 1-6 — Groupe Iso-Ressources, a
            //     French regulatory dependency scale; 1 = highest dependency)
            $table->unsignedTinyInteger('gir')->nullable();

            // --- Medical context -------------------------------------------
            $table->string('primary_doctor')->nullable();
            $table->string('primary_doctor_phone', 30)->nullable();

            // --- Emergency contact -----------------------------------------
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('emergency_contact_relationship', 50)->nullable();

            // --- Health data (RGPD Art 9 special category — ENCRYPTED AT REST)
            $table->text('medical_notes')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_history')->nullable();
            $table->text('current_treatments')->nullable();

            // --- Service lifecycle -----------------------------------------
            $table->string('status', 20)->default('active');
            $table->date('admitted_at')->nullable();
            $table->date('exited_at')->nullable();
            $table->text('exit_reason')->nullable();

            // --- RGPD Article 17 erasure flag (distinct from deleted_at) ----
            $table->timestamp('erased_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // --- Indexes ----------------------------------------------------
            $table->index(['structure_id', 'status']);
            $table->index(['structure_id', 'created_at']);
            $table->index(['structure_id', 'last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
