<?php

use App\Models\QaAnswer;
use App\Models\QaAnswerVote;
use App\Models\Structure;
use App\Models\User;

/**
 * Mandatory cross-tenant leak test for QaAnswerVote.
 */
beforeEach(function () {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

it('returns zero votes when no tenant is bound', function () {
    $structure = Structure::factory()->create();
    $answer = QaAnswer::factory()->create(['structure_id' => $structure->id]);
    $voter = User::factory()->forStructure($structure)->create();

    QaAnswerVote::factory()->forAnswer($answer)->create(['user_id' => $voter->id]);

    expect(QaAnswerVote::count())->toBe(0);
});

it('only returns votes from the current tenant', function () {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $answerA = QaAnswer::factory()->create(['structure_id' => $a->id]);
    $answerB = QaAnswer::factory()->create(['structure_id' => $b->id]);

    $voterA1 = User::factory()->forStructure($a)->create();
    $voterA2 = User::factory()->forStructure($a)->create();
    $voterB = User::factory()->forStructure($b)->create();

    QaAnswerVote::factory()->forAnswer($answerA)->create(['user_id' => $voterA1->id]);
    QaAnswerVote::factory()->forAnswer($answerA)->create(['user_id' => $voterA2->id]);
    QaAnswerVote::factory()->forAnswer($answerB)->create(['user_id' => $voterB->id]);

    app()->instance('current_structure', $a);
    expect(QaAnswerVote::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(QaAnswerVote::count())->toBe(1);
});
