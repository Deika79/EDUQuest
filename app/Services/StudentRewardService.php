<?php

namespace App\Services;

use App\Models\CoinLedgerEntry;
use App\Models\MissionAssignmentNodeReward;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Models\NodeProgress;
use App\Models\StudentRewardGrant;
use App\Models\User;

class StudentRewardService
{
    public const EXPERIENCE_PER_ACTIVITY = 10;

    public const EXPERIENCE_PER_LEVEL = 100;

    public const MAX_LEVEL = 50;

    public function grantForFirstCompletion(
        User $student,
        MissionEnrollment $enrollment,
        MissionNode $node,
        NodeProgress $progress,
    ): ?StudentRewardGrant {
        User::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();

        $existing = StudentRewardGrant::query()
            ->where('student_id', $student->id)
            ->where('node_id', $node->id)
            ->first();

        if ($existing !== null) {
            return null;
        }

        $snapshot = MissionAssignmentNodeReward::query()
            ->where('assignment_id', $enrollment->assignment_id)
            ->where('node_id', $node->id)
            ->lockForUpdate()
            ->firstOrFail();

        $grant = new StudentRewardGrant([
            'experience_awarded' => $snapshot->experience_reward,
            'coins_awarded' => $snapshot->coin_reward,
            'awarded_at' => now(),
        ]);
        $grant->student()->associate($student);
        $grant->node()->associate($node);
        $grant->firstProgress()->associate($progress);
        $grant->save();

        if ($snapshot->coin_reward > 0) {
            $entry = new CoinLedgerEntry([
                'amount' => $snapshot->coin_reward,
                'reason' => CoinLedgerEntry::REASON_ACTIVITY_COMPLETION,
            ]);
            $entry->student()->associate($student);
            $entry->rewardGrant()->associate($grant);
            $entry->save();
        }

        return $grant;
    }

    /** @return array{experience: int, level: int, coins: int} */
    public function summary(User $student): array
    {
        $experience = (int) $student->rewardGrants()->sum('experience_awarded');
        $coins = (int) $student->coinLedgerEntries()->sum('amount');

        return [
            'experience' => $experience,
            'level' => min(self::MAX_LEVEL, 1 + intdiv($experience, self::EXPERIENCE_PER_LEVEL)),
            'coins' => $coins,
        ];
    }
}
