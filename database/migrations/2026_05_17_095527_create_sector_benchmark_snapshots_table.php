<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sector_benchmark_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            // Month this snapshot covers (first day of the month, UTC).
            $table->date('snapshot_month')->index();

            // Aggregated benchmark data by [type_structure, tier].
            // Keyed: interventions, incidents, qvct, conformity.
            $table->jsonb('interventions_data');
            $table->jsonb('incidents_data');
            $table->jsonb('qvct_data');
            $table->jsonb('conformity_data');

            // Who triggered the generation (platform admin or scheduled job).
            $table->foreignId('generated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Unique per month — one canonical snapshot per period.
            $table->unique('snapshot_month');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sector_benchmark_snapshots');
    }
};
