<?php

use App\Models\Structure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * News-feed post — structure-wide announcement. Spec:
 * PHASE2_PROGRESS.md M4.4 + CDC §M4 line "Structure news feed:
 * infos, procedures, events".
 *
 * `pinned` posts surface at the top of the feed regardless of date —
 * used for permanent procedure summaries. `archived_at` is the soft
 * end-of-life marker (we don't soft-delete the row; we just hide
 * archived posts from the default feed query).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_feed_posts', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignIdFor(Structure::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('body');

            $table->foreignId('author_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->boolean('pinned')->default(false);
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['structure_id', 'pinned', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_feed_posts');
    }
};
