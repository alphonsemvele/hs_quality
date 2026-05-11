<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Document library entry. Spec: PHASE2_PROGRESS.md M4.5 + CDC §M4
 * "Document library: protocoles, fiches pratiques, formulaires".
 *
 * Files live in S3 SSE-KMS (same pipeline as InterventionPhoto). The
 * `roles_acl` JSONB lists role-name strings the document is visible
 * to (e.g. ["intervenant", "coordinateur"]) — empty/null means
 * structure-wide visibility. `version` is a monotonic counter so
 * "fiche pratique v3" sorts correctly without ambiguity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('disk')->default('s3');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');

            $table->unsignedSmallInteger('version')->default(1);

            // Role allow-list — null/empty = structure-wide.
            $table->jsonb('roles_acl')->nullable();

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
