<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QVCT questionnaire template — re-usable definition that a référent RH
 * launches as a campaign. Spec: CDC §M3 lines "Anonymous baromètre" +
 * "Parametrable frequency".
 *
 * Question shape (questions JSON):
 *   [
 *     { "key": "morale", "label": "...", "scale": "1-5", "category": "morale" },
 *     { "key": "charge", "label": "...", "scale": "1-5", "category": "surcharge" }
 *   ]
 *
 * `category` matches QvctWeakSignalType so the detector can aggregate
 * per category without hard-coded question keys.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qvct_questionnaires', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->unsignedSmallInteger('version')->default(1);

            // Cadence at which campaigns get re-launched. Stored as a string
            // for forward-compat (new cadences added later won't break casts).
            $table->string('frequency'); // QvctFrequency enum

            // Question definitions — array of {key, label, scale, category}.
            $table->jsonb('questions');

            // Active/inactive flag — archived templates remain referenced by
            // historical campaigns but cannot be re-launched.
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qvct_questionnaires');
    }
};
