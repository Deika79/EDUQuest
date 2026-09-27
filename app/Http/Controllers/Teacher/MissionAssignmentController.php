<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AssignmentClosureResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AssignMissionRequest;
use App\Models\Classroom;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Services\MissionAssignmentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MissionAssignmentController extends Controller
{
    public function store(
        AssignMissionRequest $request,
        Mission $mission,
        MissionAssignmentManager $manager,
    ): RedirectResponse {
        $classrooms = Classroom::query()
            ->whereKey($request->validated('classroom_ids'))
            ->where('teacher_id', $request->user()->id)
            ->whereNull('archived_at')
            ->get();

        $manager->assign($mission, $classrooms);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mission assigned.')]);

        return to_route('teacher.missions.show', $mission);
    }

    public function destroy(
        Mission $mission,
        MissionAssignment $assignment,
        MissionAssignmentManager $manager,
    ): RedirectResponse {
        abort_unless($assignment->mission_id === $mission->id, 404);
        Gate::authorize('close', $assignment);
        $result = $manager->close($assignment);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result === AssignmentClosureResult::Withdrawn
                ? __('Assignment withdrawn because it had no activity.')
                : __('Assignment closed and its history preserved.'),
        ]);

        return to_route('teacher.missions.show', $mission);
    }
}
