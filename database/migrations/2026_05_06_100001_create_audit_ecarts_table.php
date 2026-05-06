<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_ecarts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Structure::class)->constrained()->cascadeOnDelete();
            $table->foreignUuid('quality_audit_id')->constrained('quality_audits')->cascadeOnDelete();
            $table->string('critere', 255);
            $table->text('constat');
            $table->string('gravite', 30);
            $table->text('action_corrective')->nullable();
            $table->timestamps();

            $table->index(['structure_id', 'quality_audit_id']);
            $table->index(['structure_id', 'gravite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_ecarts');
    }
};
