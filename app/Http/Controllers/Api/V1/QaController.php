<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\AnswerQuestionRequest;
use App\Http\Requests\Communication\AskQuestionRequest;
use App\Models\QaAnswer;
use App\Models\QaQuestion;
use App\Services\QaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class QaController extends Controller
{
    public function __construct(private readonly QaService $qa) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', QaQuestion::class);

        return response()->json([
            'data' => QaQuestion::query()
                ->with(['author:id,first_name,last_name'])
                ->withCount('answers')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function show(QaQuestion $question): JsonResponse
    {
        $this->authorize('view', $question);

        $question->load([
            'author:id,first_name,last_name',
            'answers.author:id,first_name,last_name',
            'acceptedAnswer',
        ]);

        return response()->json($question);
    }

    public function ask(AskQuestionRequest $request): JsonResponse
    {
        $question = $this->qa->ask(
            currentStructure(),
            $request->user(),
            $request->validated('title'),
            $request->validated('body'),
        );

        return response()->json($question, 201);
    }

    public function answer(AnswerQuestionRequest $request, QaQuestion $question): JsonResponse
    {
        $this->authorize('view', $question);
        $this->authorize('create', QaAnswer::class);

        $answer = $this->qa->answer(
            $question,
            $request->user(),
            $request->validated('body'),
        );

        return response()->json($answer, 201);
    }

    public function acceptAnswer(Request $request, QaQuestion $question): JsonResponse
    {
        $this->authorize('acceptAnswer', $question);

        $answerId = $request->input('answer_id');
        if (! is_string($answerId) || $answerId === '') {
            throw new HttpException(422, 'answer_id requis.');
        }

        $answer = QaAnswer::query()->findOrFail($answerId);

        $updated = $this->qa->acceptAnswer($question, $answer, $request->user());

        return response()->json($updated);
    }

    public function vote(QaAnswer $answer): JsonResponse
    {
        $this->authorize('vote', $answer);

        return response()->json($this->qa->vote($answer, request()->user()));
    }
}
