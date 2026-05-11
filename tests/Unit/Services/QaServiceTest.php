<?php

declare(strict_types=1);

use App\Models\QaAnswer;
use App\Models\QaQuestion;
use App\Models\Structure;
use App\Models\User;
use App\Services\QaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(QaService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->author = User::factory()->forStructure($this->structure)->create();
});

// ── ask ───────────────────────────────────────────────────────────────────

it('asks a question scoped to the current tenant', function (): void {
    $q = $this->service->ask($this->structure, $this->author, 'Comment gérer une chute ?', 'Détail…');

    expect($q)->toBeInstanceOf(QaQuestion::class)
        ->and($q->title)->toBe('Comment gérer une chute ?')
        ->and($q->author_id)->toBe($this->author->id)
        ->and($q->structure_id)->toBe($this->structure->id)
        ->and($q->accepted_answer_id)->toBeNull();
});

// ── answer ────────────────────────────────────────────────────────────────

it('answers a question and inherits the question structure', function (): void {
    $q = QaQuestion::factory()->forStructure($this->structure)->create(['author_id' => $this->author->id]);
    $responder = User::factory()->forStructure($this->structure)->create();

    $a = $this->service->answer($q, $responder, 'Suivre le protocole §3.2');

    expect($a)->toBeInstanceOf(QaAnswer::class)
        ->and($a->qa_question_id)->toBe($q->id)
        ->and($a->author_id)->toBe($responder->id)
        ->and($a->structure_id)->toBe($this->structure->id)
        ->and($a->upvotes)->toBe(0);
});

// ── accept-answer ────────────────────────────────────────────────────────

it('lets the question author accept an answer', function (): void {
    $q = QaQuestion::factory()->forStructure($this->structure)->create(['author_id' => $this->author->id]);
    $a = QaAnswer::factory()->forQuestion($q)->create();

    $updated = $this->service->acceptAnswer($q, $a, $this->author);

    expect($updated->accepted_answer_id)->toBe($a->id);
});

it('rejects accept-answer when caller is not the question author', function (): void {
    $q = QaQuestion::factory()->forStructure($this->structure)->create(['author_id' => $this->author->id]);
    $a = QaAnswer::factory()->forQuestion($q)->create();
    $intruder = User::factory()->forStructure($this->structure)->create();

    expect(fn () => $this->service->acceptAnswer($q, $a, $intruder))
        ->toThrow(HttpException::class);
});

it('rejects accept-answer when the answer belongs to a different question', function (): void {
    $q1 = QaQuestion::factory()->forStructure($this->structure)->create(['author_id' => $this->author->id]);
    $q2 = QaQuestion::factory()->forStructure($this->structure)->create(['author_id' => $this->author->id]);
    $aFromOther = QaAnswer::factory()->forQuestion($q2)->create();

    expect(fn () => $this->service->acceptAnswer($q1, $aFromOther, $this->author))
        ->toThrow(HttpException::class);
});

// ── vote ──────────────────────────────────────────────────────────────────

it('records an upvote and increments the counter', function (): void {
    $q = QaQuestion::factory()->forStructure($this->structure)->create(['author_id' => $this->author->id]);
    $a = QaAnswer::factory()->forQuestion($q)->create();
    $voter = User::factory()->forStructure($this->structure)->create();

    $voted = $this->service->vote($a, $voter);

    expect($voted->upvotes)->toBe(1);
    expect(DB::table('qa_answer_votes')->where('user_id', $voter->id)->count())->toBe(1);
});

it('toggles the upvote off on second call (idempotent)', function (): void {
    $q = QaQuestion::factory()->forStructure($this->structure)->create(['author_id' => $this->author->id]);
    $a = QaAnswer::factory()->forQuestion($q)->create();
    $voter = User::factory()->forStructure($this->structure)->create();

    $this->service->vote($a, $voter);
    $second = $this->service->vote($a, $voter);

    expect($second->upvotes)->toBe(0);
    expect(DB::table('qa_answer_votes')->where('user_id', $voter->id)->count())->toBe(0);
});

it('counts each user only once even with multiple voters', function (): void {
    $q = QaQuestion::factory()->forStructure($this->structure)->create(['author_id' => $this->author->id]);
    $a = QaAnswer::factory()->forQuestion($q)->create();
    $u1 = User::factory()->forStructure($this->structure)->create();
    $u2 = User::factory()->forStructure($this->structure)->create();

    $this->service->vote($a, $u1);
    $this->service->vote($a, $u2);

    expect($a->fresh()->upvotes)->toBe(2);
});
