<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AiGenerationStatus;
use App\Enums\MissionSource;
use App\Enums\MissionStatus;
use App\Exceptions\AiGenerationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\GenerateMissionDraftRequest;
use App\Models\Mission;
use App\Services\MissionDraftGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AiMissionGenerationController extends Controller
{
    public function create(Request $request, MissionDraftGenerator $generator): Response
    {
        Gate::authorize('create', Mission::class);

        $dailyLimit = max(1, (int) config('services.openai.daily_limit'));
        $usedToday = $request->user()->aiGenerations()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        $activeSince = now()->subSeconds((int) config('services.openai.timeout') + 30);

        return Inertia::render('teacher/Missions/Generate', [
            'provider' => [
                'configured' => $generator->isConfigured(),
                'model' => config('services.openai.model'),
                'dailyLimit' => $dailyLimit,
                'remainingToday' => max(0, $dailyLimit - $usedToday),
                'generationInProgress' => $request->user()->aiGenerations()
                    ->where('status', AiGenerationStatus::Processing)
                    ->where('created_at', '>=', $activeSince)
                    ->exists(),
            ],
            'requestToken' => (string) Str::uuid(),
        ]);
    }

    public function store(
        GenerateMissionDraftRequest $request,
        MissionDraftGenerator $generator,
    ): RedirectResponse {
        try {
            $mission = $generator->generate(
                $request->user(),
                $request->generationInput(),
                $request->string('request_token')->toString(),
            );
        } catch (AiGenerationException $exception) {
            return back()->withInput()->withErrors(['generation' => $exception->userMessage]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('AI draft created. Review every activity before publishing.'),
        ]);

        return to_route('teacher.missions.show', $mission);
    }

    public function review(Request $request, Mission $mission): RedirectResponse
    {
        Gate::authorize('update', $mission);

        abort_unless(
            $mission->source === MissionSource::Ai && $mission->status === MissionStatus::Draft,
            404,
        );

        $generation = $mission->aiGeneration()
            ->where('teacher_id', $request->user()->id)
            ->firstOrFail();
        $generation->update(['reviewed_at' => now()]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Human review confirmed. Resolve any remaining validation errors before publishing.'),
        ]);

        return to_route('teacher.missions.show', $mission);
    }
}
