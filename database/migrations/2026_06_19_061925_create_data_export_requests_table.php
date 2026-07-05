<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks per-user GDPR (article 15 / 20) data export requests. Each row is
 * tenant-scoped via structure_id and references the requesting user. Status
 * progresses pending → processing → ready | failed | expired. Ready archives
 * live on S3 for 7 days then auto-expire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_export_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('structure_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 20)->default('pending');
            $table->string('archive_disk', 32)->nullable();
            $table->string('archive_path', 512)->nullable();
            $table->unsignedInteger('archive_size_bytes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('failure_reason', 512)->nullable();
            $table->timestamps();

            $table->foreign('structure_id')->references('id')->on('structures')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['structure_id', 'user_id', 'created_at']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_export_requests');
    }
};
