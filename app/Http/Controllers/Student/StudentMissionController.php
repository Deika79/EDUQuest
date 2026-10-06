<?php

namespace App\Http\Controllers\Student;

use App\Enums\MissionAssignmentStatus;
use App\Enums\MissionNodeType;
use App\Enums\VideoProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\CompleteMissionNodeRequest;
use App\Http\Requests\Student\SubmitQuizAttemptRequest;
use App\Models\Flashcard;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Models\QuizAnswer;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Services\NodeProgressService;
use App\Services\QuizGradingService;
use App\Services\StudentMissionAccess;
use App\Services\StudentRewardService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentMissionController extends Controller
{
    public function index(Request $request, StudentRewardService $rewards): Response
    {
        $student = $request->user();
        $profile = $student->avatarProfile()->with('equippedAppearance')->firstOrFail();
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
            'rewards' => $rewards->summary($student),
            'avatar' => [
                'name' => $profile->equippedAppearance?->name,
                'image' => $profile->equippedAppearance?->asset_path,
            ],
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
                ]) + [
                    'id' => $enrollment->assignment->mission->id,
                    'map_theme' => $enrollment->assignment->mission->map_theme->value,
                ],
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

        if ($node->type === MissionNodeType::Flashcards) {
            $node->load('flashcards');
        }

        if ($node->type === MissionNodeType::Quiz) {
            $node->load('questions.options');
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
                'quiz' => $node->type === MissionNodeType::Quiz
                    ? $this->quizData($enrollment, $node)
                    : null,
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

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Activity completed. Points, XP and coins are awarded only on the first valid completion.'),
        ]);

        return to_route('student.missions.show', $enrollment);
    }

    public function submitQuiz(
        SubmitQuizAttemptRequest $request,
        MissionEnrollment $enrollment,
        MissionNode $node,
        QuizGradingService $grading,
    ): RedirectResponse {
        $attempt = $grading->grade(
            $request->user(),
            $enrollment,
            $node,
            $request->validated('answers'),
        );

        Inertia::flash('toast', [
            'type' => $attempt->passed ? 'success' : 'info',
            'message' => $attempt->passed
                ? __('Questionnaire passed. Points, XP and coins are awarded only on the first valid completion.')
                : __('Attempt saved. Review the feedback and try again.'),
        ]);

        return to_route('student.missions.nodes.show', [$enrollment, $node]);
    }

    /** @return array<string, mixed> */
    private function quizData(MissionEnrollment $enrollment, MissionNode $node): array
    {
        $attempts = $enrollment->quizAttempts()->where('node_id', $node->id);
        $latestAttempt = (clone $attempts)
            ->with('answers')
            ->latest('submitted_at')
            ->latest('id')
            ->first();
        $answerLookup = $latestAttempt?->answers->keyBy('question_id');

        $feedback = $latestAttempt === null ? null : [
            'score' => round((float) $latestAttempt->score, 2),
            'correct_answers' => $latestAttempt->correct_answers,
            'total_questions' => $latestAttempt->total_questions,
            'passed' => $latestAttempt->passed,
            'answers' => $node->questions->map(function (QuizQuestion $question) use ($answerLookup): array {
                $answer = $answerLookup?->get($question->id);
                $correctOption = $question->options->firstWhere('is_correct', true);
                abort_unless($answer instanceof QuizAnswer && $correctOption instanceof QuizOption, 500);

                return [
                    'question_id' => $question->id,
                    'selected_option_id' => $answer->option_id,
                    'correct_option_id' => $correctOption->id,
                    'correct' => $answer->is_correct,
                    'explanation' => $question->explanation,
                ];
            })->values(),
        ];

        return [
            'pass_threshold' => $node->pass_threshold ?? 70,
            'questions' => $node->questions->map(fn (QuizQuestion $question): array => [
                'id' => $question->id,
                'position' => $question->position,
                'statement' => $question->statement,
                'options' => $question->options->map(fn (QuizOption $option): array => [
                    'id' => $option->id,
                    'position' => $option->position,
                    'text' => $option->text,
                ])->values(),
            ])->values(),
            'attempt_count' => (clone $attempts)->count(),
            'best_score' => round((float) ((clone $attempts)->max('score') ?? 0), 2),
            'ever_passed' => (clone $attempts)->where('passed', true)->exists(),
            'latest_feedback' => $feedback,
        ];
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
