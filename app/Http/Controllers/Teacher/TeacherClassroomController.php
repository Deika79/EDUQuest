<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreClassroomRequest;
use App\Http\Requests\Teacher\UpdateClassroomRequest;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeacherClassroomController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Classroom::class);

        return Inertia::render('teacher/Classrooms/Index', [
            'classrooms' => $request->user()->ownedClassrooms()
                ->withCount([
                    'memberships as active_students_count' => fn ($query) => $query->where('active', true),
                ])
                ->orderBy('name')
                ->get(['id', 'name', 'level', 'subject', 'teacher_id']),
        ]);
    }

    public function store(StoreClassroomRequest $request): RedirectResponse
    {
        $classroom = new Classroom($request->validated());
        $classroom->teacher()->associate($request->user());
        $classroom->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Class created.')]);

        return to_route('teacher.classrooms.show', $classroom);
    }

    public function show(Classroom $classroom): Response
    {
        Gate::authorize('view', $classroom);

        $classroom->load([
            'memberships' => fn ($query) => $query
                ->with('student:id,name,username,email,active,created_by')
                ->orderByDesc('active')
                ->orderBy('id'),
        ]);

        return Inertia::render('teacher/Classrooms/Show', [
            'classroom' => $classroom->only(['id', 'name', 'level', 'subject']),
            'memberships' => $classroom->memberships->map(fn ($membership) => [
                'id' => $membership->id,
                'active' => $membership->active,
                'student' => [
                    'id' => $membership->student->id,
                    'name' => $membership->student->name,
                    'username' => $membership->student->username,
                    'email' => $membership->student->email,
                    'account_active' => $membership->student->active,
                    'managed_by_current_teacher' => $membership->student->created_by === $classroom->teacher_id,
                ],
            ]),
        ]);
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        $classroom->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Class updated.')]);

        return to_route('teacher.classrooms.show', $classroom);
    }
}
