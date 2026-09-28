<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\MissionNodeType;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\MissionAssignment;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeacherTrackingController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Classroom::class);

        $classrooms = $request->user()->ownedClassrooms()
            ->with([
                'missionAssignments' => fn ($query) => $query
                    ->with('mission:id,title,subject,level')
                    ->withCount('enrollments')
                    ->latest('assigned_at'),
            ])
            ->orderBy('name')
            ->get(['id', 'teacher_id', 'name', 'level', 'subject']);

        return Inertia::render('teacher/Tracking/Index', [
            'classrooms' => $classrooms->map(fn (Classroom $classroom): array => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'level' => $classroom->level,
                'subject' => $classroom->subject,
                'assignments' => $classroom->missionAssignments->map(fn (MissionAssignment $assignment): array => [
                    'id' => $assignment->id,
                    'status' => $assignment->status->value,
                    'enrollments_count' => $assignment->enrollments_count,
                    'mission' => $assignment->mission->only(['id', 'title', 'subject', 'level']),
                ])->values(),
            ]),
        ]);
    }

    public function show(Classroom $classroom, MissionAssignment $assignment): Response
    {
        $this->authorizeNestedAssignment($classroom, $assignment);
        $assignment->load('mission.nodes:id,mission_id');

        $enrollments = $assignment->enrollments()
            ->with([
                'student:id,name,username,active',
                'progress:id,enrollment_id,node_id,completed_at,points_awarded',
                'quizAttempts:id,enrollment_id,node_id,score,passed,submitted_at',
            ])
            ->get()
            ->sortBy('student.name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
        $memberships = $classroom->memberships()
            ->whereIn('student_id', $enrollments->pluck('student_id'))
            ->get(['student_id', 'active'])
            ->keyBy('student_id');
        $totalNodes = $assignment->mission->nodes->count();

        return Inertia::render('teacher/Tracking/Show', [
            'classroom' => $classroom->only(['id', 'name', 'level', 'subject']),
            'assignment' => $this->assignmentData($assignment),
            'enrollments' => $enrollments->map(fn (MissionEnrollment $enrollment): array => $this->enrollmentSummary(
                $enrollment,
                $totalNodes,
                (bool) $memberships->get($enrollment->student_id)?->active,
            )),
        ]);
    }

    public function enrollment(
        Classroom $classroom,
        MissionAssignment $assignment,
        MissionEnrollment $enrollment,
    ): Response {
        $this->authorizeNestedAssignment($classroom, $assignment);
        abort_unless($enrollment->assignment_id === $assignment->id, 404);
        Gate::authorize('view', $enrollment);

        $assignment->load('mission.nodes:id,mission_id,position,type,title');
        $enrollment->load([
            'student:id,name,username,active',
            'progress:id,enrollment_id,node_id,completed_at,points_awarded',
            'quizAttempts:id,enrollment_id,node_id,score,correct_answers,total_questions,passed,submitted_at',
        ]);
        $membershipActive = (bool) $classroom->memberships()
            ->where('student_id', $enrollment->student_id)
            ->value('active');
        $totalNodes = $assignment->mission->nodes->count();

        return Inertia::render('teacher/Tracking/Enrollment', [
            'classroom' => $classroom->only(['id', 'name', 'level', 'subject']),
            'assignment' => $this->assignmentData($assignment),
            'enrollment' => [
                ...$this->enrollmentSummary($enrollment, $totalNodes, $membershipActive),
                'nodes' => $assignment->mission->nodes->map(
                    fn (MissionNode $node): array => $this->nodeData($node, $enrollment),
                ),
            ],
        ]);
    }

    private function authorizeNestedAssignment(Classroom $classroom, MissionAssignment $assignment): void
    {
        Gate::authorize('view', $classroom);
        abort_unless($assignment->classroom_id === $classroom->id, 404);
        Gate::authorize('view', $assignment);
    }

    /** @return array{id: int, status: string, mission: array<string, mixed>} */
    private function assignmentData(MissionAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'status' => $assignment->status->value,
            'mission' => $assignment->mission->only(['id', 'title', 'subject', 'level']),
        ];
    }

    /** @return array<string, mixed> */
    private function enrollmentSummary(
        MissionEnrollment $enrollment,
        int $totalNodes,
        bool $membershipActive,
    ): array {
        $completedNodes = $enrollment->progress->count();
        $attemptCount = $enrollment->quizAttempts->count();
        $bestScore = $enrollment->quizAttempts->max('score');

        return [
            'id' => $enrollment->id,
            'student' => $enrollment->student->only(['name', 'username']),
            'account_active' => $enrollment->student->active,
            'membership_active' => $membershipActive,
            'enrollment_active' => $enrollment->active,
            'status' => $this->progressStatus($completedNodes, $totalNodes, $attemptCount),
            'completed_nodes' => $completedNodes,
            'total_nodes' => $totalNodes,
            'progress_percent' => $this->percentage($completedNodes, $totalNodes),
            'points' => (int) $enrollment->progress->sum('points_awarded'),
            'attempt_count' => $attemptCount,
            'best_score' => $bestScore === null ? null : round((float) $bestScore, 2),
        ];
    }

    /** @return array<string, mixed> */
    private function nodeData(MissionNode $node, MissionEnrollment $enrollment): array
    {
        $progress = $enrollment->progress->firstWhere('node_id', $node->id);
        /** @var Collection<int, QuizAttempt> $attempts */
        $attempts = $enrollment->quizAttempts
            ->where('node_id', $node->id)
            ->sortByDesc('submitted_at')
            ->values();
        $bestScore = $attempts->max('score');

        return [
            'id' => $node->id,
            'position' => $node->position,
            'type' => $node->type->value,
            'title' => $node->title,
            'completed' => $progress !== null,
            'completed_at' => $progress?->completed_at?->toIso8601String(),
            'points' => $progress === null ? 0 : $progress->points_awarded,
            'quiz' => $node->type === MissionNodeType::Quiz ? [
                'attempt_count' => $attempts->count(),
                'best_score' => $bestScore === null ? null : round((float) $bestScore, 2),
                'ever_passed' => $attempts->contains('passed', true),
                'attempts' => $attempts->map(fn ($attempt): array => [
                    'id' => $attempt->id,
                    'score' => round((float) $attempt->score, 2),
                    'correct_answers' => $attempt->correct_answers,
                    'total_questions' => $attempt->total_questions,
                    'passed' => $attempt->passed,
                    'submitted_at' => $attempt->submitted_at->toIso8601String(),
                ])->values(),
            ] : null,
        ];
    }

    private function progressStatus(int $completedNodes, int $totalNodes, int $attemptCount): string
    {
        if ($totalNodes > 0 && $completedNodes === $totalNodes) {
            return 'completed';
        }

        return $completedNodes > 0 || $attemptCount > 0 ? 'in_progress' : 'not_started';
    }

    private function percentage(int $completed, int $total): int
    {
        return $total === 0 ? 0 : (int) round(($completed / $total) * 100);
    }
}
