<?php

namespace App\Services;

use App\Enums\MissionNodeType;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizGradingService
{
    public function __construct(
        private readonly StudentMissionAccess $access,
        private readonly NodeProgressService $progress,
    ) {}

    /**
     * @param  list<array{question_id: int, option_id: int}>  $answers
     */
    public function grade(
        User $student,
        MissionEnrollment $enrollment,
        MissionNode $node,
        array $answers,
    ): QuizAttempt {
        return DB::transaction(function () use ($student, $enrollment, $node, $answers): QuizAttempt {
            $lockedEnrollment = MissionEnrollment::query()
                ->with('assignment')
                ->lockForUpdate()
                ->findOrFail($enrollment->id);
            $lockedNode = MissionNode::query()->lockForUpdate()->findOrFail($node->id);
            $this->access->assertNode($student, $lockedEnrollment, $lockedNode);
            abort_unless($lockedNode->type === MissionNodeType::Quiz, 404);

            $questions = $lockedNode->questions()->with('options')->get();
            $answerLookup = collect($answers)->keyBy('question_id');

            if ($questions->isEmpty() || $answerLookup->count() !== $questions->count()) {
                throw ValidationException::withMessages([
                    'answers' => 'Answer every question from this questionnaire exactly once.',
                ]);
            }

            $gradedAnswers = $questions->map(function (QuizQuestion $question) use ($answerLookup): array {
                $submitted = $answerLookup->get($question->id);
                $option = is_array($submitted)
                    ? $question->options->firstWhere('id', (int) $submitted['option_id'])
                    : null;

                if ($option === null) {
                    throw ValidationException::withMessages([
                        'answers' => 'Every selected option must belong to its question.',
                    ]);
                }

                return [
                    'question_id' => $question->id,
                    'option_id' => $option->id,
                    'is_correct' => $option->is_correct,
                ];
            });

            $total = $questions->count();
            $correct = $gradedAnswers->where('is_correct', true)->count();
            $score = ($correct / $total) * 100;
            $threshold = $lockedNode->pass_threshold ?? 70;
            $passed = ($correct * 100) >= ($threshold * $total);

            $attempt = $lockedEnrollment->quizAttempts()->create([
                'node_id' => $lockedNode->id,
                'score' => $score,
                'correct_answers' => $correct,
                'total_questions' => $total,
                'passed' => $passed,
                'submitted_at' => now(),
            ]);

            $attempt->answers()->createMany($gradedAnswers->all());
            $lockedEnrollment->forceFill([
                'activity_started_at' => $lockedEnrollment->activity_started_at ?? now(),
            ])->save();

            if ($passed) {
                $this->progress->completePassedQuiz($student, $lockedEnrollment, $lockedNode);
            }

            return $attempt;
        });
    }
}
