<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\MoveMissionNodeRequest;
use App\Http\Requests\Teacher\SaveMissionNodeRequest;
use App\Models\Mission;
use App\Models\MissionNode;
use App\Services\MissionNodeWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MissionNodeController extends Controller
{
    public function store(
        SaveMissionNodeRequest $request,
        Mission $mission,
        MissionNodeWriter $writer,
    ): RedirectResponse {
        $writer->save($mission, null, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mission node added.')]);

        return to_route('teacher.missions.show', $mission);
    }

    public function update(
        SaveMissionNodeRequest $request,
        Mission $mission,
        MissionNode $node,
        MissionNodeWriter $writer,
    ): RedirectResponse {
        $writer->save($mission, $node, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mission node updated.')]);

        return to_route('teacher.missions.show', $mission);
    }

    public function destroy(Mission $mission, MissionNode $node): RedirectResponse
    {
        Gate::authorize('update', $mission);
        abort_unless($node->mission_id === $mission->id, 404);

        DB::transaction(function () use ($mission, $node): void {
            $position = $node->position;
            $mission->nodes()->lockForUpdate()->get();
            $node->delete();

            $mission->nodes()
                ->where('position', '>', $position)
                ->orderBy('position')
                ->get()
                ->each(function (MissionNode $followingNode): void {
                    $followingNode->decrement('position');
                });
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mission node removed.')]);

        return to_route('teacher.missions.show', $mission);
    }

    public function move(
        MoveMissionNodeRequest $request,
        Mission $mission,
        MissionNode $node,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $mission, $node): void {
            $moving = $mission->nodes()->lockForUpdate()->findOrFail($node->id);
            $targetPosition = $request->validated('direction') === 'up'
                ? max(0, $moving->position - 1)
                : $moving->position + 1;
            $target = $mission->nodes()->where('position', $targetPosition)->first();

            if ($target === null) {
                return;
            }

            $originalPosition = $moving->position;
            $moving->position = 0;
            $moving->save();
            $target->position = $originalPosition;
            $target->save();
            $moving->position = $targetPosition;
            $moving->save();
        });

        return to_route('teacher.missions.show', $mission);
    }
}
