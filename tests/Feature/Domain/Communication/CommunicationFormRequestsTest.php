<?php

declare(strict_types=1);

use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Communication\AnswerQuestionRequest;
use App\Http\Requests\Communication\AskQuestionRequest;
use App\Http\Requests\Communication\EditMessageRequest;
use App\Http\Requests\Communication\PublishNewsRequest;
use App\Http\Requests\Communication\SendMessageRequest;
use App\Http\Requests\Communication\UploadDocumentRequest;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

/**
 * Helper that runs a Form Request's rules against a payload and
 * returns the Validator. We don't go through the HTTP layer because
 * Form Requests need a bound auth user for authorize(); we test rules
 * here and authorize() in the policy + controller integration tests.
 */
function validateRequest(string $requestClass, array $payload): Illuminate\Validation\Validator
{
    /** @var BaseFormRequest $req */
    $req = new $requestClass;

    return Validator::make($payload, $req->rules());
}

// ── SendMessageRequest ───────────────────────────────────────────────────

it('SendMessage requires a non-empty body', function (): void {
    expect(validateRequest(SendMessageRequest::class, [])->fails())->toBeTrue();
    expect(validateRequest(SendMessageRequest::class, ['body' => ''])->fails())->toBeTrue();
    expect(validateRequest(SendMessageRequest::class, ['body' => 'Bonjour'])->fails())->toBeFalse();
});

it('SendMessage caps body at 8000 characters', function (): void {
    $tooLong = str_repeat('a', 8001);
    expect(validateRequest(SendMessageRequest::class, ['body' => $tooLong])->fails())->toBeTrue();
});

it('SendMessage accepts up to 5 attachments', function (): void {
    $attachments = array_fill(0, 5, ['name' => 'f.pdf', 'path' => 's3://b/f.pdf']);
    expect(validateRequest(SendMessageRequest::class, [
        'body' => 'voir pj',
        'attachments' => $attachments,
    ])->fails())->toBeFalse();
});

it('SendMessage rejects more than 5 attachments', function (): void {
    $attachments = array_fill(0, 6, ['name' => 'f.pdf', 'path' => 's3://b/f.pdf']);
    expect(validateRequest(SendMessageRequest::class, [
        'body' => 'voir pj',
        'attachments' => $attachments,
    ])->fails())->toBeTrue();
});

// ── EditMessageRequest ──────────────────────────────────────────────────

it('EditMessage requires a body', function (): void {
    expect(validateRequest(EditMessageRequest::class, [])->fails())->toBeTrue();
    expect(validateRequest(EditMessageRequest::class, ['body' => 'corrigé'])->fails())->toBeFalse();
});

// ── PublishNewsRequest ──────────────────────────────────────────────────

it('PublishNews requires title and body', function (): void {
    expect(validateRequest(PublishNewsRequest::class, ['body' => 'corps'])->fails())->toBeTrue();
    expect(validateRequest(PublishNewsRequest::class, ['title' => 'Titre'])->fails())->toBeTrue();
    expect(validateRequest(PublishNewsRequest::class, [
        'title' => 'Titre',
        'body' => 'Corps',
    ])->fails())->toBeFalse();
});

it('PublishNews caps body at 20000 characters', function (): void {
    $tooLong = str_repeat('a', 20001);
    expect(validateRequest(PublishNewsRequest::class, [
        'title' => 'Titre',
        'body' => $tooLong,
    ])->fails())->toBeTrue();
});

// ── UploadDocumentRequest ───────────────────────────────────────────────

it('UploadDocument accepts a valid PDF', function (): void {
    $file = UploadedFile::fake()->create('proc.pdf', 100, 'application/pdf');
    expect(validateRequest(UploadDocumentRequest::class, [
        'title' => 'Procédure soins',
        'file' => $file,
    ])->fails())->toBeFalse();
});

it('UploadDocument rejects an executable MIME', function (): void {
    $file = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');
    expect(validateRequest(UploadDocumentRequest::class, [
        'title' => 'Procédure',
        'file' => $file,
    ])->fails())->toBeTrue();
});

it('UploadDocument rejects an oversized file (> 25 MB)', function (): void {
    // 26 MB
    $file = UploadedFile::fake()->create('huge.pdf', 26 * 1024, 'application/pdf');
    expect(validateRequest(UploadDocumentRequest::class, [
        'title' => 'Procédure',
        'file' => $file,
    ])->fails())->toBeTrue();
});

// ── AskQuestionRequest / AnswerQuestionRequest ──────────────────────────

it('AskQuestion requires title and body', function (): void {
    expect(validateRequest(AskQuestionRequest::class, [])->fails())->toBeTrue();
    expect(validateRequest(AskQuestionRequest::class, [
        'title' => 'Comment ?',
        'body' => 'Détail…',
    ])->fails())->toBeFalse();
});

it('AnswerQuestion requires a non-empty body', function (): void {
    expect(validateRequest(AnswerQuestionRequest::class, [])->fails())->toBeTrue();
    expect(validateRequest(AnswerQuestionRequest::class, ['body' => 'Réponse'])->fails())->toBeFalse();
});
