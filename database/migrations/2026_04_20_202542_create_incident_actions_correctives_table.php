<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_actions_correctives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Structure::class)->constrained()->cascadeOnDelete();
            $table->foreignUuid('incident_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('echeance')->nullable();
            $table->string('statut', 20)->default('pending');
            $table->timestamp('realise_at')->nullable();
            $table->timestamps();

            $table->index(['structure_id', 'incident_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_actions_correctives');
    }
};
