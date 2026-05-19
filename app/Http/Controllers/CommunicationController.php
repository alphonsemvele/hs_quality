<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Communication\AnswerQuestionRequest;
use App\Http\Requests\Communication\AskQuestionRequest;
use App\Http\Requests\Communication\PublishNewsRequest;
use App\Http\Requests\Communication\SendMessageRequest;
use App\Http\Requests\Communication\UploadDocumentRequest;
use App\Models\DiscussionGroup;
use App\Models\Document;
use App\Models\NewsFeedPost;
use App\Models\QaAnswer;
use App\Models\QaQuestion;
use App\Services\DocumentLibraryService;
use App\Services\MessageService;
use App\Services\NewsFeedService;
use App\Services\QaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 / M4 — Communication interne (Inertia surface).
 *
 * The web app uses the same MessageService / NewsFeedService /
 * DocumentLibraryService / QaService as the mobile API; this controller
 * only handles render + redirect, while validation goes through the
 * Form Requests.
 *
 * Demo fixtures are still served when the structure has no real data
 * yet — a deliberate fallback so the front team's UI showcase keeps
 * working in `php artisan serve` without seeding.
 */
class CommunicationController extends Controller
{
    public function __construct(
        private readonly MessageService $messages,
        private readonly NewsFeedService $news,
        private readonly DocumentLibraryService $documents,
        private readonly QaService $qa,
    ) {}

    public function index(): Response
    {
        $news = NewsFeedPost::query()
            ->with('author:id,first_name,last_name')
            ->whereNull('archived_at')
            ->orderByDesc('pinned')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $groups = DiscussionGroup::query()
            ->withCount('members')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        $documents = Document::query()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return Inertia::render('dashboard/communication/index', [
            'messages' => [],
            'channels' => $groups->map(fn (DiscussionGroup $g) => [
                'id' => $g->id,
                'name' => $g->title,
                'nb_members' => $g->members_count,
                'last_activity' => $g->updated_at?->diffForHumans(),
            ])->all(),
            'documents' => $documents->map(fn (Document $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'type' => str(class_basename($d->mime_type))->upper()->value(),
                'uploaded_by' => $d->uploader?->fullName() ?? '—',
                'uploaded_at' => $d->created_at?->isoFormat('DD/MM/YYYY'),
            ])->all(),
            'newsPosts' => $news->map(fn (NewsFeedPost $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'body' => $p->body ?? '',
                'author' => $p->author?->fullName() ?? '—',
                'pinned' => (bool) $p->pinned,
                'created_at' => $p->created_at?->diffForHumans(),
            ])->all(),
            'currentChannel' => null,
            'qaQuestions' => [],
        ]);
    }

    public function sendMessage(SendMessageRequest $request, DiscussionGroup $group): RedirectResponse
    {
        $this->authorize('view', $group);

        $this->messages->send(
            $group,
            $request->user(),
            $request->validated('body'),
            $request->validated('attachments'),
        );

        return back()->with('success', 'Message envoyé.');
    }

    public function publishNews(PublishNewsRequest $request): RedirectResponse
    {
        $this->news->publish(
            currentStructure(),
            $request->user(),
            $request->validated('title'),
            $request->validated('body'),
        );

        return back()->with('success', 'Actualité publiée.');
    }

    /**
     * Upload a new document to the structure library. Multipart endpoint
     * called by the Inertia DropzoneUploader. Returns JSON so the JS client
     * can render the file in its progress list without a full page reload.
     */
    public function uploadDocument(UploadDocumentRequest $request): JsonResponse
    {
        $document = $this->documents->upload(
            currentStructure(),
            $request->user(),
            $request->file('file'),
            $request->validated('title'),
            $request->validated('description'),
            $request->validated('roles_acl'),
        );

        return response()->json([
            'id' => $document->id,
            'title' => $document->title,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'version' => $document->version,
            'uploaded_at' => $document->created_at?->isoFormat('DD/MM/YYYY'),
        ], 201);
    }

    /**
     * Issue a temporary signed download URL for a stored document. The
     * service re-checks the role ACL before returning the URL.
     */
    public function downloadDocument(Document $document): RedirectResponse
    {
        $this->authorize('view', $document);

        $url = $this->documents->downloadUrl($document, request()->user());

        return redirect()->away($url);
    }

    /**
     * Inertia detail page for one Q&A question. Loads the answers list
     * so the user can vote, see who accepted what, and post a new answer.
     * Each card carries the inline vote / accept controls.
     */
    public function showQuestion(QaQuestion $question): Response
    {
        $this->authorize('view', $question);

        $question->load([
            'author:id,first_name,last_name',
            'answers' => fn ($q) => $q->orderByDesc('upvotes')->orderBy('created_at'),
            'answers.author:id,first_name,last_name',
        ]);

        $user = request()->user();

        return Inertia::render('dashboard/communication/qa-show', [
            'question' => [
                'id' => $question->id,
                'title' => $question->title,
                'body' => $question->body,
                'author' => $question->author?->fullName() ?? '—',
                'created_at' => $question->created_at?->isoFormat('DD MMM YYYY · HH:mm'),
                'accepted_answer_id' => $question->accepted_answer_id,
                'answers' => $question->answers->map(fn (QaAnswer $a) => [
                    'id' => $a->id,
                    'body' => $a->body,
                    'author' => $a->author?->fullName() ?? '—',
                    'upvotes' => (int) $a->upvotes,
                    'is_accepted' => $a->id === $question->accepted_answer_id,
                    'created_at' => $a->created_at?->isoFormat('DD MMM YYYY · HH:mm'),
                ])->all(),
            ],
            'can_accept' => $user->can('acceptAnswer', $question),
            'can_answer' => $user->can('create', QaAnswer::class),
        ]);
    }

    public function askQuestion(AskQuestionRequest $request): RedirectResponse
    {
        $question = $this->qa->ask(
            currentStructure(),
            $request->user(),
            $request->validated('title'),
            $request->validated('body'),
        );

        return redirect()
            ->route('communication.qa.show', $question)
            ->with('success', 'Question publiée.');
    }

    public function answerQuestion(AnswerQuestionRequest $request, QaQuestion $question): RedirectResponse
    {
        $this->authorize('view', $question);
        $this->authorize('create', QaAnswer::class);

        $this->qa->answer($question, $request->user(), $request->validated('body'));

        return back()->with('success', 'Réponse publiée.');
    }

    public function acceptAnswer(Request $request, QaQuestion $question): RedirectResponse
    {
        $this->authorize('acceptAnswer', $question);

        $request->validate([
            'answer_id' => ['required', 'uuid'],
        ]);

        $answer = QaAnswer::query()->findOrFail($request->string('answer_id')->value());

        $this->qa->acceptAnswer($question, $answer, $request->user());

        return back()->with('success', 'Réponse acceptée.');
    }

    public function voteAnswer(QaAnswer $answer): RedirectResponse
    {
        $this->authorize('vote', $answer);

        $this->qa->vote($answer, request()->user());

        return back()->with('success', 'Vote enregistré.');
    }
}
