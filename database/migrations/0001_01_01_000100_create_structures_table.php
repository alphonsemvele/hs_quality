<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('nom');
            $table->string('type', 20);
            $table->text('adresse')->nullable();
            $table->string('siret', 14)->nullable();
            $table->string('tier', 20)->default('essentiel');
            $table->string('statut', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('statut');
            $table->index('tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structures');
    }
};
