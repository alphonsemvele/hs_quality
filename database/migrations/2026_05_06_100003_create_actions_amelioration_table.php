<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actions_amelioration', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Structure::class)->constrained()->cascadeOnDelete();
            $table->foreignUuid('plan_amelioration_id')->constrained('plans_amelioration')->cascadeOnDelete();
            $table->text('description');
            $table->string('responsable', 150)->nullable();
            $table->date('echeance')->nullable();
            $table->string('statut', 30)->default('planifiee');
            $table->timestamp('realise_at')->nullable();
            $table->timestamps();

            $table->index(['structure_id', 'plan_amelioration_id']);
            $table->index(['structure_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actions_amelioration');
    }
};
