<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prediction_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            // structures.id is uuid; users.id is bigint (from $table->id()).
            $table->foreignUuid('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('type');    // PredictionType enum value
            $table->string('status');  // PredictionStatus enum value

            // Snapshot of input data sent to the ML service —
            // stored so results are reproducible even if source records change.
            $table->jsonb('input_snapshot');

            // Result payload returned by the ML service. Null until complete.
            $table->jsonb('result')->nullable();

            // Human-readable error from the ML service on failure.
            $table->text('error_message')->nullable();

            // Opaque job identifier returned by the ML service on submission.
            $table->string('ml_job_id')->nullable()->index();

            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Lookup: latest prediction per structure + type for dashboard.
            $table->index(['structure_id', 'type', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_requests');
    }
};
