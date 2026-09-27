<?php

namespace App\Http\Controllers\Student;

use App\Enums\MissionAssignmentStatus;
use App\Enums\MissionNodeType;
use App\Enums\VideoProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\CompleteMissionNodeRequest;
use App\Models\Flashcard;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Services\NodeProgressService;
use App\Services\StudentMissionAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentMissionController extends Controller
{
    public function index(Request $request): Response
    {
        $student = $request->user();
        $enrollments = MissionEnrollment::query()
            ->where('student_id', $student->id)
            ->where('active', true)
            ->whereHas('assignment', fn (Builder $query) => $query
                ->where('status', MissionAssignmentStatus::Open))
            ->whereHas('assignment.classroom.memberships', fn (Builder $query) => $query
                ->where('student_id', $student->id)
                ->where('active', true))
            ->with(['assignment.mission', 'assignment.classroom'])
            ->withCount('progress')
            ->withSum('progress', 'points_awarded')
            ->latest('activated_at')
            ->get();

        return Inertia::render('student/Dashboard', [
            'missions' => $enrollments->map(function (MissionEnrollment $enrollment): array {
                $totalNodes = $enrollment->assignment->mission->nodes()->count();

                return [
                    'enrollment_id' => $enrollment->id,
                    'title' => $enrollment->assignment->mission->title,
                    'description' => $enrollment->assignment->mission->description,
                    'subject' => $enrollment->assignment->mission->subject,
                    'level' => $enrollment->assignment->mission->level,
                    'classroom' => $enrollment->assignment->classroom->name,
                    'completed_nodes' => $enrollment->progress_count,
                    'total_nodes' => $totalNodes,
                    'progress_percent' => $this->percentage($enrollment->progress_count, $totalNodes),
                    'points' => (int) ($enrollment->progress_sum_points_awarded ?? 0),
                ];
            }),
        ]);
    }

    public function show(
        Request $request,
        MissionEnrollment $enrollment,
        StudentMissionAccess $access,
    ): Response {
        $access->assertEnrollment($request->user(), $enrollment);
        $enrollment->loadMissing(['assignment.mission', 'assignment.classroom']);
        $totalNodes = $enrollment->assignment->mission->nodes()->count();
        $completedNodes = $enrollment->progress()->count();

        return Inertia::render('student/Missions/Show', [
            'enrollment' => [
                'id' => $enrollment->id,
                'mission' => $enrollment->assignment->mission->only([
                    'title', 'description', 'subject', 'level',
                ]),
                'classroom' => $enrollment->assignment->classroom->only(['name']),
                'completed_nodes' => $completedNodes,
                'total_nodes' => $totalNodes,
                'progress_percent' => $this->percentage($completedNodes, $totalNodes),
                'points' => (int) $enrollment->progress()->sum('points_awarded'),
            ],
            'nodes' => $access->map($enrollment),
        ]);
    }

    public function activity(
        Request $request,
        MissionEnrollment $enrollment,
        MissionNode $node,
        StudentMissionAccess $access,
    ): Response {
        $access->assertNode($request->user(), $enrollment, $node);
        abort_if($node->type === MissionNodeType::Quiz, 403, 'Questionnaires are not available yet.');

        if ($node->type === MissionNodeType::Flashcards) {
            $node->load('flashcards');
        }

        return Inertia::render('student/Missions/Activity', [
            'enrollmentId' => $enrollment->id,
            'node' => [
                'id' => $node->id,
                'position' => $node->position,
                'type' => $node->type->value,
                'title' => $node->title,
                'body' => $node->type === MissionNodeType::Explanation ? $node->body : null,
                'video' => $node->type === MissionNodeType::Video ? $this->videoData($node) : null,
                'flashcards' => $node->type === MissionNodeType::Flashcards
                    ? $node->flashcards->map(fn (Flashcard $card) => $card->only(['id', 'front', 'back']))
                    : [],
                'completed' => $enrollment->progress()->where('node_id', $node->id)->exists(),
            ],
        ]);
    }

    public function complete(
        CompleteMissionNodeRequest $request,
        MissionEnrollment $enrollment,
        MissionNode $node,
        NodeProgressService $progress,
    ): RedirectResponse {
        $progress->complete($request->user(), $enrollment, $node);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Activity completed. Ten points awarded once.')]);

        return to_route('student.missions.show', $enrollment);
    }

    /** @return array{embed_url: string, external_url: string} */
    private function videoData(MissionNode $node): array
    {
        abort_unless($node->video_provider !== null && $node->video_id !== null, 404);

        return match ($node->video_provider) {
            VideoProvider::YouTube => [
                'embed_url' => "https://www.youtube-nocookie.com/embed/{$node->video_id}",
                'external_url' => "https://www.youtube.com/watch?v={$node->video_id}",
            ],
            VideoProvider::Vimeo => [
                'embed_url' => "https://player.vimeo.com/video/{$node->video_id}",
                'external_url' => "https://vimeo.com/{$node->video_id}",
            ],
        };
    }

    private function percentage(int $completed, int $total): int
    {
        return $total === 0 ? 0 : (int) round(($completed / $total) * 100);
    }
}
