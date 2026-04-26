<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervention_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Structure::class)->constrained()->cascadeOnDelete();
            $table->foreignUuid('intervention_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('s3');
            $table->string('path');
            $table->string('mime_type', 50);
            $table->unsignedInteger('size_bytes');
            $table->string('original_name')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index(['structure_id', 'intervention_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervention_photos');
    }
};
