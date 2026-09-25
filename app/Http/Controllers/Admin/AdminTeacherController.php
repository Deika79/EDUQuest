<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeacherRequest;
use App\Http\Requests\Admin\UpdateTeacherStatusRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminTeacherController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('admin/Dashboard', [
            'teachers' => User::query()
                ->where('role', UserRole::Teacher)
                ->orderBy('name')
                ->get(['id', 'name', 'username', 'email', 'active', 'must_change_password']),
        ]);
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $teacher = new User;
        $teacher->fill($data);
        $teacher->role = UserRole::Teacher;
        $teacher->active = true;
        $teacher->must_change_password = true;
        $teacher->creator()->associate($request->user());
        $teacher->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Teacher created.')]);

        return to_route('admin.dashboard');
    }

    public function update(UpdateTeacherStatusRequest $request, User $teacher): RedirectResponse
    {
        $teacher->active = $request->boolean('active');
        $teacher->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Teacher status updated.')]);

        return to_route('admin.dashboard');
    }
}
