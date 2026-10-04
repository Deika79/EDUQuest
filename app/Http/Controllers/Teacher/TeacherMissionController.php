<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\MissionSource;
use App\Enums\MissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreMissionRequest;
use App\Http\Requests\Teacher\UpdateMissionRequest;
use App\Models\Flashcard;
use App\Models\Mission;
use App\Models\MissionAssignment;
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
                ->withCount(['nodes', 'assignments'])
                ->latest('updated_at')
                ->get(['id', 'title', 'description', 'subject', 'level', 'status', 'source', 'teacher_id', 'updated_at']),
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
        $mission->load([
            'aiGeneration',
            'nodes.questions.options',
            'nodes.flashcards',
            'assignments.classroom',
            'assignments.enrollments',
        ]);

        return Inertia::render('teacher/Missions/Show', [
            'mission' => [
                ...$mission->only(['id', 'title', 'description', 'subject', 'level']),
                'status' => $mission->status->value,
                'source' => $mission->source->value,
                'ai_review_required' => $mission->aiGeneration !== null
                    && $mission->aiGeneration->reviewed_at === null,
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
                'coin_reward' => $node->coin_reward,
                'review_required' => $node->review_required,
                'review_note' => $node->review_note,
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
            'classrooms' => $mission->teacher->ownedClassrooms()
                ->whereNull('archived_at')
                ->orderBy('name')
                ->get(['id', 'name', 'level', 'subject']),
            'assignments' => $mission->assignments
                ->sortByDesc('assigned_at')
                ->values()
                ->map(fn (MissionAssignment $assignment) => [
                    'id' => $assignment->id,
                    'status' => $assignment->status->value,
                    'assigned_at' => $assignment->assigned_at,
                    'closed_at' => $assignment->closed_at,
                    'classroom' => $assignment->classroom->only(['id', 'name', 'level', 'subject']),
                    'enrollments_count' => $assignment->enrollments->count(),
                    'active_enrollments_count' => $assignment->enrollments->where('active', true)->count(),
                ]),
        ]);
    }

    public function update(UpdateMissionRequest $request, Mission $mission): RedirectResponse
    {
        $mission->update($request->validated());
        $mission->aiGeneration()->update(['reviewed_at' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mission draft updated.')]);

        return to_route('teacher.missions.show', $mission);
    }
}
