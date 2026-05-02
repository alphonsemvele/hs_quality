<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Action item inside a PAC. Spec: PHASE2_PROGRESS.md M6.6.
 *
 * source_audit_response_id is nullable + nullOnDelete so an auto-
 * generated action keeps a back-pointer to the audit response that
 * triggered it (for traceability) without coupling lifecycle: deleting
 * the original audit response should not cascade-delete the action.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pac_actions', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('pac_id')
                ->constrained('pacs')
                ->cascadeOnDelete();

            $table->foreignUuid('source_audit_response_id')
                ->nullable()
                ->constrained('audit_run_responses')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->foreignId('responsible_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('due_date')->nullable();

            // PacActionStatus enum.
            $table->string('status')->default('pending');

            $table->string('evidence_url')->nullable();

            $table->timestamps();

            $table->index(['structure_id', 'pac_id', 'status']);
            $table->index(['structure_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pac_actions');
    }
};
