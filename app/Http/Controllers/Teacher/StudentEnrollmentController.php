<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\EnrollExistingStudentRequest;
use App\Http\Requests\Teacher\StoreStudentRequest;
use App\Http\Requests\Teacher\UpdateMembershipRequest;
use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StudentEnrollmentController extends Controller
{
    public function store(StoreStudentRequest $request, Classroom $classroom): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $classroom, $data): void {
            $student = new User;
            $student->fill($data);
            $student->role = UserRole::Student;
            $student->active = true;
            $student->must_change_password = true;
            $student->creator()->associate($request->user());
            $student->save();

            $classroom->memberships()->create([
                'student_id' => $student->id,
                'active' => true,
                'activated_at' => now(),
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Student created and enrolled.')]);

        return to_route('teacher.classrooms.show', $classroom);
    }

    public function storeExisting(EnrollExistingStudentRequest $request, Classroom $classroom): RedirectResponse
    {
        $student = User::query()
            ->where('username', $request->validated('username'))
            ->where('role', UserRole::Student)
            ->where('active', true)
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'existing_username' => __('No active student account matches that username.'),
            ]);
        }

        $membership = $classroom->memberships()->firstOrCreate(
            ['student_id' => $student->id],
            ['active' => true, 'activated_at' => now()],
        );

        if (! $membership->wasRecentlyCreated) {
            throw ValidationException::withMessages([
                'existing_username' => $membership->active
                    ? __('This student is already enrolled in the class.')
                    : __('This student already has an inactive membership. Use reinstate instead.'),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Existing student enrolled.')]);

        return to_route('teacher.classrooms.show', $classroom);
    }

    public function update(
        UpdateMembershipRequest $request,
        Classroom $classroom,
        ClassroomMembership $membership,
    ): RedirectResponse {
        Gate::authorize('view', $classroom);
        abort_unless($membership->classroom_id === $classroom->id, 404);

        $active = $request->boolean('active');
        $membership->forceFill([
            'active' => $active,
            'activated_at' => $active ? now() : $membership->activated_at,
            'deactivated_at' => $active ? null : now(),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Enrollment status updated.')]);

        return to_route('teacher.classrooms.show', $classroom);
    }
}
