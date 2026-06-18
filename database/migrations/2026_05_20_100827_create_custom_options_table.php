<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('structure_id')->constrained()->cascadeOnDelete();

            // field_key must match a case in App\Enums\CustomOptionField.
            $table->string('field_key', 80);

            // The value submitted in forms. Immutable once created so that
            // existing domain rows (beneficiaries.gir, incidents.categorie…)
            // remain consistent.
            $table->string('value', 100);

            // French display label shown in dropdowns.
            $table->string('label', 200);

            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'field_key', 'is_active']);
        });

        // Partial unique index: value unique per (structure_id, field_key) among live rows.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX custom_options_unique_active_value
                 ON custom_options (structure_id, field_key, value)
                 WHERE deleted_at IS NULL'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_options');
    }
};
