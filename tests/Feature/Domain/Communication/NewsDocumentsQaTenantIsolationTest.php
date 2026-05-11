<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\NewsFeedPost;
use App\Models\QaAnswer;
use App\Models\QaQuestion;
use App\Models\Structure;

/**
 * Cross-tenant leak tests for the M4.4-M4.6 communication tables
 * (news feed, document library, Q&A forum). PHASE2_PROGRESS.md M4.8
 * partial.
 */
beforeEach(function (): void {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

it('only returns news_feed_posts from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    NewsFeedPost::factory()->forStructure($a)->count(2)->create();
    NewsFeedPost::factory()->forStructure($b)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(NewsFeedPost::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(NewsFeedPost::count())->toBe(3);
});

it('only returns documents from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    Document::factory()->forStructure($a)->count(1)->create();
    Document::factory()->forStructure($b)->count(4)->create();

    app()->instance('current_structure', $a);
    expect(Document::count())->toBe(1);

    app()->instance('current_structure', $b);
    expect(Document::count())->toBe(4);
});

it('only returns qa_questions from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    QaQuestion::factory()->forStructure($a)->count(2)->create();
    QaQuestion::factory()->forStructure($b)->count(2)->create();

    app()->instance('current_structure', $a);
    expect(QaQuestion::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(QaQuestion::count())->toBe(2);
});

it('only returns qa_answers from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $qA = QaQuestion::factory()->forStructure($a)->create();
    $qB = QaQuestion::factory()->forStructure($b)->create();

    QaAnswer::factory()->forQuestion($qA)->count(2)->create();
    QaAnswer::factory()->forQuestion($qB)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(QaAnswer::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(QaAnswer::count())->toBe(3);
});
