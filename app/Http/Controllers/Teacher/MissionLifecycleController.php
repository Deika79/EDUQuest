<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use App\Services\MissionLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MissionLifecycleController extends Controller
{
    public function publish(Mission $mission, MissionLifecycle $lifecycle): RedirectResponse
    {
        Gate::authorize('publish', $mission);
        $lifecycle->publish($mission);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mission published.')]);

        return to_route('teacher.missions.show', $mission);
    }

    public function duplicate(Mission $mission, MissionLifecycle $lifecycle): RedirectResponse
    {
        Gate::authorize('duplicate', $mission);
        $copy = $lifecycle->duplicate($mission);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mission duplicated as a draft.')]);

        return to_route('teacher.missions.show', $copy);
    }

    public function archive(Mission $mission, MissionLifecycle $lifecycle): RedirectResponse
    {
        Gate::authorize('archive', $mission);
        $lifecycle->archive($mission);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mission archived.')]);

        return to_route('teacher.missions.show', $mission);
    }
}
