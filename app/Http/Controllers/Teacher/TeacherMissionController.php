<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\MissionSource;
use App\Enums\MissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreMissionRequest;
use App\Http\Requests\Teacher\UpdateMissionRequest;
use App\Models\Flashcard;
use App\Models\Mission;
use App\Models\MissionNode;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Services\MissionReadiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeacherMissionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Mission::class);

        return Inertia::render('teacher/Missions/Index', [
            'missions' => $request->user()->missions()
                ->where('status', MissionStatus::Draft)
                ->withCount('nodes')
                ->latest('updated_at')
                ->get(['id', 'title', 'description', 'subject', 'level', 'status', 'teacher_id', 'updated_at']),
        ]);
    }

    public function store(StoreMissionRequest $request): RedirectResponse
    {
        $mission = new Mission($request->validated());
        $mission->status = MissionStatus::Draft;
        $mission->source = MissionSource::Manual;
        $mission->teacher()->associate($request->user());
        $mission->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mission draft created.')]);

        return to_route('teacher.missions.show', $mission);
    }

    public function show(Mission $mission, MissionReadiness $readiness): Response
    {
        Gate::authorize('view', $mission);
        $mission->load(['nodes.questions.options', 'nodes.flashcards']);

        return Inertia::render('teacher/Missions/Show', [
            'mission' => [
                ...$mission->only(['id', 'title', 'description', 'subject', 'level']),
                'status' => $mission->status->value,
            ],
            'nodes' => $mission->nodes->map(fn (MissionNode $node) => [
                'id' => $node->id,
                'position' => $node->position,
                'type' => $node->type->value,
                'title' => $node->title,
                'body' => $node->body,
                'video_provider' => $node->video_provider?->value,
                'video_reference' => $node->video_id,
                'pass_threshold' => $node->pass_threshold,
                'questions' => $node->questions->map(fn (QuizQuestion $question) => [
                    'id' => $question->id,
                    'statement' => $question->statement,
                    'explanation' => $question->explanation,
                    'options' => $question->options->map(fn (QuizOption $option) => [
                        'id' => $option->id,
                        'text' => $option->text,
                        'is_correct' => $option->is_correct,
                    ]),
                ]),
                'flashcards' => $node->flashcards->map(fn (Flashcard $flashcard) => [
                    'id' => $flashcard->id,
                    'front' => $flashcard->front,
                    'back' => $flashcard->back,
                ]),
            ]),
            'readiness' => $readiness->check($mission),
        ]);
    }

    public function update(UpdateMissionRequest $request, Mission $mission): RedirectResponse
    {
        $mission->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mission draft updated.')]);

        return to_route('teacher.missions.show', $mission);
    }
}
