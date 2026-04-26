<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Structure::class)->constrained()->cascadeOnDelete();
            $table->foreignId('declared_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('beneficiary_id')->nullable()->constrained('beneficiaries')->nullOnDelete();
            $table->foreignUuid('intervention_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->string('categorie', 50);
            $table->string('gravite', 30);
            $table->string('statut', 30)->default('declare');
            $table->text('description');
            $table->string('lieu')->nullable();
            $table->boolean('avec_deces')->default(false);
            $table->boolean('avec_hospitalisation')->default(false);
            $table->boolean('avec_blessure_physique')->default(false);
            $table->text('analyse_causes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('notifie_responsable_at')->nullable();
            $table->timestamp('notifie_ars_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'statut']);
            $table->index(['structure_id', 'gravite']);
            $table->index(['structure_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
