<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impersonation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('impersonator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('impersonated_user_id')->constrained('users')->cascadeOnDelete();
            // structure_id follows project convention; enables HDS queries like
            // "all platform accesses for Structure X in the past 12 months".
            // No BelongsToStructure global scope — this is a platform-level log.
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->text('reason');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->timestamps();

            $table->index(['structure_id', 'started_at']);
            $table->index(['impersonated_user_id', 'started_at']);
            $table->index(['impersonator_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
    }
};
