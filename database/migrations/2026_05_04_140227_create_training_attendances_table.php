<?php

use App\Models\Structure;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user attendance record on a training session. Spec:
 * PHASE2_PROGRESS.md M5.5.
 *
 * Unique (session, user) prevents double-registration. Status
 * transitions are owned by TrainingPlanService — any direct status
 * write outside the service is a bug.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_attendances', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(TrainingSession::class, 'training_session_id')
                ->constrained('training_sessions')
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('status')->default('registered'); // TrainingAttendanceStatus
            $table->text('notes')->nullable(); // trainer's post-session note

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('attended_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->unique(['training_session_id', 'user_id'], 'training_attendance_unique');
            $table->index(['structure_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_attendances');
    }
};
