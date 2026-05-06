<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans_amelioration', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Structure::class)->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titre', 255);
            $table->string('source', 30);
            // Free-form pointer to the originating record (audit_id, incident_id…).
            // Polymorphic-lite: the source column already disambiguates the type.
            $table->string('source_id')->nullable();
            $table->text('constat')->nullable();
            $table->string('responsable', 150)->nullable();
            $table->date('echeance')->nullable();
            $table->string('statut', 30)->default('ouvert');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'statut']);
            $table->index(['structure_id', 'source', 'source_id']);
            $table->index(['structure_id', 'echeance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans_amelioration');
    }
};
