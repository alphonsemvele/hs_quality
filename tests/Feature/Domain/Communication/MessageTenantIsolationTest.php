<?php

declare(strict_types=1);

use App\Models\DiscussionGroup;
use App\Models\Message;
use App\Models\Structure;
use App\Models\User;

/**
 * Cross-tenant leak tests for the M4 communication tables.
 * PHASE2_PROGRESS.md M4.8 partial.
 */
beforeEach(function (): void {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

it('only returns discussion groups from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    DiscussionGroup::factory()->forStructure($a)->count(2)->create();
    DiscussionGroup::factory()->forStructure($b)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(DiscussionGroup::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(DiscussionGroup::count())->toBe(3);
});

it('cannot find a foreign-tenant discussion group by id', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $foreign = DiscussionGroup::factory()->forStructure($b)->create();

    app()->instance('current_structure', $a);
    expect(DiscussionGroup::find($foreign->id))->toBeNull();
});

it('only returns messages from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $groupA = DiscussionGroup::factory()->forStructure($a)->create();
    $groupB = DiscussionGroup::factory()->forStructure($b)->create();

    Message::factory()->inGroup($groupA)->count(2)->create();
    Message::factory()->inGroup($groupB)->count(4)->create();

    app()->instance('current_structure', $a);
    expect(Message::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(Message::count())->toBe(4);
});

it('encrypts the message body at rest', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);

    $group = DiscussionGroup::factory()->forStructure($structure)->create();
    $author = User::factory()->forStructure($structure)->create();

    $message = Message::factory()->inGroup($group, $author)->create([
        'body' => 'Sensitive coordination note about a beneficiary visit.',
    ]);

    $rawRow = DB::table('messages')->where('id', $message->id)->first();
    expect($rawRow->body)->not->toContain('Sensitive');
    expect($message->fresh()->body)->toBe('Sensitive coordination note about a beneficiary visit.');
});
