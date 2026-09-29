<?php

use App\Http\Controllers\Admin\AdminTeacherController;
use App\Http\Controllers\Auth\RequiredPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Student\AvatarSetupController;
use App\Http\Controllers\Student\StudentMissionController;
use App\Http\Controllers\Teacher\AiMissionGenerationController;
use App\Http\Controllers\Teacher\MissionAssignmentController;
use App\Http\Controllers\Teacher\MissionLifecycleController;
use App\Http\Controllers\Teacher\MissionNodeController;
use App\Http\Controllers\Teacher\StudentEnrollmentController;
use App\Http\Controllers\Teacher\TeacherClassroomController;
use App\Http\Controllers\Teacher\TeacherMissionController;
use App\Http\Controllers\Teacher\TeacherTrackingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('password/change-required', [RequiredPasswordController::class, 'edit'])
        ->name('password.change.edit');
    Route::put('password/change-required', [RequiredPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.change.update');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:administrator')->group(function () {
        Route::get('admin', [AdminTeacherController::class, 'index'])->name('admin.dashboard');
        Route::post('admin/teachers', [AdminTeacherController::class, 'store'])->name('admin.teachers.store');
        Route::patch('admin/teachers/{teacher}', [AdminTeacherController::class, 'update'])
            ->name('admin.teachers.update');
    });

    Route::inertia('teacher', 'teacher/Dashboard')
        ->middleware('role:teacher')
        ->name('teacher.dashboard');

    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('classes', [TeacherClassroomController::class, 'index'])->name('classrooms.index');
        Route::post('classes', [TeacherClassroomController::class, 'store'])->name('classrooms.store');
        Route::get('classes/{classroom}', [TeacherClassroomController::class, 'show'])->name('classrooms.show');
        Route::patch('classes/{classroom}', [TeacherClassroomController::class, 'update'])->name('classrooms.update');
        Route::post('classes/{classroom}/students', [StudentEnrollmentController::class, 'store'])
            ->name('classrooms.students.store');
        Route::post('classes/{classroom}/students/existing', [StudentEnrollmentController::class, 'storeExisting'])
            ->name('classrooms.students.existing.store');
        Route::patch('classes/{classroom}/memberships/{membership}', [StudentEnrollmentController::class, 'update'])
            ->name('classrooms.memberships.update');

        Route::get('tracking', [TeacherTrackingController::class, 'index'])->name('tracking.index');
        Route::get('tracking/classes/{classroom}/assignments/{assignment}', [TeacherTrackingController::class, 'show'])
            ->name('tracking.show');
        Route::get(
            'tracking/classes/{classroom}/assignments/{assignment}/enrollments/{enrollment}',
            [TeacherTrackingController::class, 'enrollment'],
        )->name('tracking.enrollments.show');

        Route::get('missions', [TeacherMissionController::class, 'index'])->name('missions.index');
        Route::post('missions', [TeacherMissionController::class, 'store'])->name('missions.store');
        Route::get('missions/generate', [AiMissionGenerationController::class, 'create'])
            ->name('missions.generate.create');
        Route::post('missions/generate', [AiMissionGenerationController::class, 'store'])
            ->middleware('throttle:ai-generation')
            ->name('missions.generate.store');
        Route::get('missions/{mission}', [TeacherMissionController::class, 'show'])->name('missions.show');
        Route::post('missions/{mission}/ai-review', [AiMissionGenerationController::class, 'review'])
            ->name('missions.ai-review');
        Route::patch('missions/{mission}', [TeacherMissionController::class, 'update'])->name('missions.update');
        Route::post('missions/{mission}/publish', [MissionLifecycleController::class, 'publish'])
            ->name('missions.publish');
        Route::post('missions/{mission}/duplicate', [MissionLifecycleController::class, 'duplicate'])
            ->name('missions.duplicate');
        Route::post('missions/{mission}/archive', [MissionLifecycleController::class, 'archive'])
            ->name('missions.archive');
        Route::post('missions/{mission}/assignments', [MissionAssignmentController::class, 'store'])
            ->name('missions.assignments.store');
        Route::delete('missions/{mission}/assignments/{assignment}', [MissionAssignmentController::class, 'destroy'])
            ->name('missions.assignments.destroy');
        Route::post('missions/{mission}/nodes', [MissionNodeController::class, 'store'])->name('missions.nodes.store');
        Route::patch('missions/{mission}/nodes/{node}', [MissionNodeController::class, 'update'])
            ->name('missions.nodes.update');
        Route::delete('missions/{mission}/nodes/{node}', [MissionNodeController::class, 'destroy'])
            ->name('missions.nodes.destroy');
        Route::patch('missions/{mission}/nodes/{node}/position', [MissionNodeController::class, 'move'])
            ->name('missions.nodes.move');
    });
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('avatar/setup', [AvatarSetupController::class, 'edit'])->name('avatar.setup.edit');
        Route::post('avatar/setup', [AvatarSetupController::class, 'store'])->name('avatar.setup.store');

        Route::middleware('avatar.configured')->group(function () {
            Route::get('/', [StudentMissionController::class, 'index'])->name('dashboard');
            Route::get('missions', [StudentMissionController::class, 'index'])->name('missions.index');
            Route::get('missions/{enrollment}', [StudentMissionController::class, 'show'])->name('missions.show');
            Route::get('missions/{enrollment}/nodes/{node}', [StudentMissionController::class, 'activity'])
                ->name('missions.nodes.show');
            Route::post('missions/{enrollment}/nodes/{node}/complete', [StudentMissionController::class, 'complete'])
                ->name('missions.nodes.complete');
            Route::post('missions/{enrollment}/nodes/{node}/quiz-attempts', [StudentMissionController::class, 'submitQuiz'])
                ->middleware('throttle:5,1')
                ->name('missions.nodes.quiz-attempts.store');
        });
    });
});

require __DIR__.'/settings.php';
