<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('structure_id')->constrained('structures')->cascadeOnDelete();

            $table->unsignedSmallInteger('year');

            // Report status mirrors AuditRunPdfService pattern.
            $table->string('status'); // AnnualReportStatus enum

            // S3 path of the generated PDF.
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            // One report per structure per year.
            $table->unique(['structure_id', 'year']);
            $table->index(['structure_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_reports');
    }
};
