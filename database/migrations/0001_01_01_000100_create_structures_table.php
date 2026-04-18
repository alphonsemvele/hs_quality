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
            $table->string('name');
            $table->string('type', 20);
            $table->text('address')->nullable();
            $table->string('siret', 14)->nullable();
            $table->string('tier', 20)->default('essential');
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('status');
            $table->index('tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structures');
    }
};
