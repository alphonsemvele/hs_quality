<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 6 — domain audits (HAS, AFNOR, ISO 9001, internal). Named
 * `quality_audits` to avoid colliding with the `audits` table created by
 * owen-it/laravel-auditing for the cross-cutting audit trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Structure::class)->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titre', 255);
            $table->string('referentiel', 30);
            $table->text('description')->nullable();
            $table->date('date_audit')->nullable();
            $table->string('auditeur', 150)->nullable();
            $table->string('statut', 30)->default('planifie');
            $table->unsignedTinyInteger('score')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'statut']);
            $table->index(['structure_id', 'date_audit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_audits');
    }
};
