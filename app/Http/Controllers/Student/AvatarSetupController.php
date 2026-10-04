<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreAvatarProfileRequest;
use App\Models\AvatarProfile;
use App\Models\User;
use App\Services\AvatarShopService;
use App\Support\AvatarOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AvatarSetupController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        Gate::authorize('create', AvatarProfile::class);

        if ($request->user()->avatarProfile()->exists()) {
            return to_route('student.dashboard');
        }

        return Inertia::render('student/Avatar/Setup', [
            'characters' => AvatarOptions::forFrontend(),
            'selection' => AvatarOptions::defaultSelection(),
        ]);
    }

    public function store(StoreAvatarProfileRequest $request, AvatarShopService $shop): RedirectResponse
    {
        DB::transaction(function () use ($request, $shop): void {
            $student = User::query()->lockForUpdate()->findOrFail($request->user()->id);

            if ($student->avatarProfile()->exists()) {
                return;
            }

            $profile = new AvatarProfile([
                ...$request->safe()->only(['character_key']),
                'setup_completed_at' => now(),
            ]);
            $profile->student()->associate($student);
            $profile->save();
            $shop->grantStarter($student, $profile);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Avatar guardado. Ya puedes continuar con tus misiones.'),
        ]);

        return to_route('student.dashboard');
    }
}
