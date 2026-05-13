<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Communication\PublishNewsRequest;
use App\Http\Requests\Communication\SendMessageRequest;
use App\Http\Requests\Communication\UploadDocumentRequest;
use App\Models\DiscussionGroup;
use App\Models\Document;
use App\Models\NewsFeedPost;
use App\Services\DocumentLibraryService;
use App\Services\MessageService;
use App\Services\NewsFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
    ) {}

    public function index(): Response
    {
        $structure = currentStructure();

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

        $hasRealData = $news->isNotEmpty() || $groups->isNotEmpty() || $documents->isNotEmpty();

        return Inertia::render('dashboard/communication/index', [
            'messages' => $hasRealData ? [] : $this->demoMessages(),
            'channels' => $hasRealData
                ? $groups->map(fn (DiscussionGroup $g) => [
                    'id' => $g->id,
                    'name' => $g->title,
                    'nb_members' => $g->members_count,
                    'last_activity' => $g->updated_at?->diffForHumans(),
                ])->all()
                : $this->demoChannels(),
            'documents' => $hasRealData
                ? $documents->map(fn (Document $d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'type' => str(class_basename($d->mime_type))->upper()->value(),
                    'uploaded_by' => $d->uploader?->fullName() ?? '—',
                    'uploaded_at' => $d->created_at?->isoFormat('DD/MM/YYYY'),
                ])->all()
                : $this->demoDocuments(),
            'newsPosts' => $news->isNotEmpty()
                ? $news->map(fn (NewsFeedPost $p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'body' => $p->body ?? '',
                    'author' => $p->author?->fullName() ?? '—',
                    'pinned' => (bool) $p->pinned,
                    'created_at' => $p->created_at?->diffForHumans(),
                ])->all()
                : $this->demoNewsPosts(),
            'currentChannel' => $hasRealData ? null : $this->demoCurrentChannel(),
            'qaQuestions' => $hasRealData ? [] : $this->demoQaQuestions(),
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

    /** @return list<array<string, mixed>> */
    private function demoMessages(): array
    {
        return [
            ['id' => 'm1', 'author' => 'Sophie Martin', 'initials' => 'SM', 'content' => 'Rappel : réunion d\'équipe ce vendredi à 14h en visio. Ordre du jour : bilan Q1 et planification Q2.', 'channel' => 'general', 'created_at' => 'Aujourd\'hui, 09:15'],
            ['id' => 'm2', 'author' => 'Claire Bernard', 'initials' => 'CB', 'content' => 'Le nouveau protocole de transmission numérique est en ligne. Merci de le consulter et de confirmer la prise de connaissance avant vendredi.', 'channel' => 'qualite', 'created_at' => 'Hier, 16:30'],
            ['id' => 'm3', 'author' => 'Thomas Dupont', 'initials' => 'TD', 'content' => 'Planning de la semaine prochaine publié. Marie et Luc, merci de vérifier vos créneaux et signaler tout conflit.', 'channel' => 'coordination', 'created_at' => 'Hier, 11:00'],
            ['id' => 'm4', 'author' => 'Anne Petit', 'initials' => 'AP', 'content' => 'Les sessions de formation PSC1 sont ouvertes à l\'inscription. Priorité aux intervenants dont la certification expire avant septembre.', 'channel' => 'rh', 'created_at' => '02/05, 14:20'],
            ['id' => 'm5', 'author' => 'Marie Leclerc', 'initials' => 'ML', 'content' => 'Retour terrain : le tapis antidérapant chez Mme D. est bien en place, elle se sent rassurée.', 'channel' => 'terrain', 'created_at' => '01/05, 17:45'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoChannels(): array
    {
        return [
            ['id' => 'ch1', 'name' => 'Général', 'nb_members' => 6, 'last_activity' => 'Aujourd\'hui'],
            ['id' => 'ch2', 'name' => 'Coordination', 'nb_members' => 3, 'last_activity' => 'Hier'],
            ['id' => 'ch3', 'name' => 'Qualité & Audits', 'nb_members' => 2, 'last_activity' => 'Hier'],
            ['id' => 'ch4', 'name' => 'Terrain', 'nb_members' => 4, 'last_activity' => '01/05'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoDocuments(): array
    {
        return [
            ['id' => 'd1', 'title' => 'Protocole de transmission numérique v2', 'type' => 'PDF', 'size_kb' => 312, 'uploaded_by' => 'Claire Bernard', 'uploaded_at' => '12/04/2026'],
            ['id' => 'd2', 'title' => 'Check-list évaluation domicile', 'type' => 'PDF', 'size_kb' => 145, 'uploaded_by' => 'Claire Bernard', 'uploaded_at' => '28/04/2026'],
            ['id' => 'd3', 'title' => 'Planning Mai 2026', 'type' => 'XLSX', 'size_kb' => 84, 'uploaded_by' => 'Thomas Dupont', 'uploaded_at' => '30/04/2026'],
            ['id' => 'd4', 'title' => 'Protocole médicamenteux révisé', 'type' => 'PDF', 'size_kb' => 218, 'uploaded_by' => 'Claire Bernard', 'uploaded_at' => '28/03/2026'],
            ['id' => 'd5', 'title' => 'Affiche gestes barrières', 'type' => 'PNG', 'size_kb' => 1245, 'uploaded_by' => 'Anne Petit', 'uploaded_at' => '15/03/2026'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoNewsPosts(): array
    {
        return [
            [
                'id' => 'n1',
                'title' => 'Nouvelle convention HAS — préparation visite de juin',
                'body' => 'La visite HAS aura lieu les 18-19 juin. Le référent qualité organise une réunion préparatoire vendredi 24 mai à 10h. La participation de chaque coordinateur·rice est attendue.',
                'author' => 'Claire Bernard',
                'pinned' => true,
                'created_at' => 'Il y a 2 jours',
            ],
            [
                'id' => 'n2',
                'title' => 'Bienvenue à Sophie B. — nouvelle intervenante secteur Nord',
                'body' => "Sophie nous rejoint à partir du 15 mai sur le secteur Nord. Merci de lui réserver un accueil chaleureux et de l'accompagner sur ses premières tournées.",
                'author' => 'Thomas Dupont',
                'pinned' => false,
                'created_at' => 'Il y a 5 jours',
            ],
            [
                'id' => 'n3',
                'title' => 'Formation PSC1 — sessions ouvertes',
                'body' => 'Les sessions de juin sont planifiées. Priorité aux intervenants dont la certification expire avant septembre. Inscriptions dans Formations → Sessions.',
                'author' => 'Anne Petit',
                'pinned' => false,
                'created_at' => 'Il y a 1 semaine',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function demoCurrentChannel(): array
    {
        return [
            'id' => 'ch1',
            'name' => 'Général',
            'description' => 'Échanges transverses de la structure',
            'nb_members' => 6,
            'messages' => [
                [
                    'id' => 't1',
                    'author' => 'Sophie Martin',
                    'initials' => 'SM',
                    'content' => "Rappel : réunion d'équipe ce vendredi à 14h en visio. Ordre du jour : bilan Q1 et planification Q2.",
                    'created_at' => '09:15',
                    'is_self' => false,
                ],
                [
                    'id' => 't2',
                    'author' => 'Thomas Dupont',
                    'initials' => 'TD',
                    'content' => 'Noté, je prépare un point planning. Sophie, peux-tu me partager les chiffres d\'activité Q1 d\'ici jeudi ?',
                    'created_at' => '09:18',
                    'is_self' => false,
                ],
                [
                    'id' => 't3',
                    'author' => 'Moi',
                    'initials' => 'MO',
                    'content' => 'Je joins l\'export à la fin de la matinée 👍',
                    'created_at' => '09:22',
                    'is_self' => true,
                ],
                [
                    'id' => 't4',
                    'author' => 'Claire Bernard',
                    'initials' => 'CB',
                    'content' => "N'oubliez pas d'évoquer la prépa visite HAS — j'ai posté une actu pinglée.",
                    'created_at' => '09:25',
                    'is_self' => false,
                ],
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoQaQuestions(): array
    {
        return [
            [
                'id' => 'q1',
                'title' => 'Procédure exact pour refus de prise médicamenteuse ?',
                'asker' => 'Marie Leclerc',
                'votes' => 4,
                'answers_count' => 2,
                'accepted' => true,
                'created_at' => 'Il y a 3 jours',
                'preview' => 'Une bénéficiaire refuse systématiquement son traitement du matin. Quelle est la conduite à tenir : notifier immédiatement, attendre la fin de la journée, contacter le médecin ?',
            ],
            [
                'id' => 'q2',
                'title' => 'Quel formulaire pour signaler une chute sans conséquence ?',
                'asker' => 'Luc Moreau',
                'votes' => 2,
                'answers_count' => 1,
                'accepted' => false,
                'created_at' => 'Il y a 1 semaine',
                'preview' => "Chute observée hier matin, aucune blessure ni douleur. Je voudrais quand même tracer l'incident pour la famille.",
            ],
            [
                'id' => 'q3',
                'title' => "Délai d'enregistrement d'un nouveau bénéficiaire ?",
                'asker' => 'Sophie Bernard',
                'votes' => 1,
                'answers_count' => 0,
                'accepted' => false,
                'created_at' => 'Il y a 2 semaines',
                'preview' => 'Sous quel délai dois-je terminer la fiche bénéficiaire après l\'évaluation à domicile ?',
            ],
        ];
    }
}
