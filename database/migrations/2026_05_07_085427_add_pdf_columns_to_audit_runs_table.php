<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_runs', function (Blueprint $table) {
            // S3 path of the rendered PDF (after a finalised run is exported).
            // Null = not yet generated. Disk is always 's3' (matches the
            // Phase 1 InterventionMediaService convention).
            $table->string('pdf_path')->nullable()->after('finalised_at');
            $table->timestamp('pdf_generated_at')->nullable()->after('pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('audit_runs', function (Blueprint $table) {
            $table->dropColumn(['pdf_path', 'pdf_generated_at']);
        });
    }
};
