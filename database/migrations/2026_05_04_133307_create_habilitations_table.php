<?php

use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Habilitations — qualifications a person holds (DEAS, AS, IDEL,
 * AVS, AMP, etc.). Spec: PHASE2_PROGRESS.md M5.1.
 *
 * Distinct from `certifications`: a habilitation is a structural
 * qualification (the diploma never expires once awarded, though the
 * person may need to keep CPD up). A certification is renewable on
 * a fixed cadence (BLS every 2 years, etc.). The two live in
 * separate tables because their lifecycles and alerting rules differ.
 *
 * `valid_from` / `valid_until` are nullable: most habilitations are
 * lifetime, but some structures need to record a temporary or
 * conditional validity (e.g. a stage probationaire).
 *
 * `evidence_path` points to S3 SSE-KMS storage of the diploma scan.
 * Audited because the dirigeant can be challenged in an ARS audit
 * to prove every intervenant has the required habilitation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habilitations', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type'); // DEAS, AS, IDEL, AVS, AMP, ...
            $table->string('reference_number')->nullable(); // diploma number / RNCP code
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('evidence_path')->nullable(); // S3 path

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'user_id']);
            $table->index(['structure_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habilitations');
    }
};
