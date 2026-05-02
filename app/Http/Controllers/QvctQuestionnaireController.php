<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Qvct\StoreQvctQuestionnaireRequest;
use App\Models\QvctQuestionnaire;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QvctQuestionnaireController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', QvctQuestionnaire::class);

        return Inertia::render('dashboard/qvct/questionnaires/index', [
            'questionnaires' => QvctQuestionnaire::query()
                ->orderByDesc('created_at')
                ->paginate(20),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', QvctQuestionnaire::class);

        return Inertia::render('dashboard/qvct/questionnaires/create');
    }

    public function store(StoreQvctQuestionnaireRequest $request): RedirectResponse
    {
        $questionnaire = QvctQuestionnaire::create([
            ...$request->validated(),
            'is_active' => true,
            'version' => 1,
        ]);

        return redirect()
            ->route('qvct.questionnaires.show', $questionnaire)
            ->with('success', 'Questionnaire créé.');
    }

    public function show(QvctQuestionnaire $questionnaire): Response
    {
        $this->authorize('view', $questionnaire);

        $questionnaire->load(['campaigns' => fn ($q) => $q->latest()->limit(10)]);

        return Inertia::render('dashboard/qvct/questionnaires/show', [
            'questionnaire' => $questionnaire,
        ]);
    }

    public function archive(QvctQuestionnaire $questionnaire): RedirectResponse
    {
        $this->authorize('update', $questionnaire);

        $questionnaire->update(['is_active' => false]);

        return back()->with('success', 'Questionnaire archivé.');
    }

    public function destroy(QvctQuestionnaire $questionnaire): RedirectResponse
    {
        $this->authorize('delete', $questionnaire);

        $questionnaire->delete();

        return redirect()->route('qvct.questionnaires.index')
            ->with('success', 'Questionnaire supprimé.');
    }
}
