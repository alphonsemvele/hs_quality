<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discussion group — a conversation thread scoped to a structure.
 * Spec: PHASE2_PROGRESS.md M4.1 + CDC §M4 line "Discussion groups:
 * per team, per secteur, per thematic".
 *
 * The `kind` column tags what the group represents (team / secteur /
 * thematic / direct) so the UI can render the right header. `direct`
 * groups are 1:1 conversations between two specific users — the
 * pivot table holds both members.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_groups', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // Free-text tag so the catalog doesn't grow with each new
            // grouping idea — UI can switch icons by string match.
            $table->string('kind')->default('thematic');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_groups');
    }
};
