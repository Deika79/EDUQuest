<?php

use App\Http\Controllers\Admin\AdminTeacherController;
use App\Http\Controllers\Auth\RequiredPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teacher\MissionNodeController;
use App\Http\Controllers\Teacher\StudentEnrollmentController;
use App\Http\Controllers\Teacher\TeacherClassroomController;
use App\Http\Controllers\Teacher\TeacherMissionController;
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

        Route::get('missions', [TeacherMissionController::class, 'index'])->name('missions.index');
        Route::post('missions', [TeacherMissionController::class, 'store'])->name('missions.store');
        Route::get('missions/{mission}', [TeacherMissionController::class, 'show'])->name('missions.show');
        Route::patch('missions/{mission}', [TeacherMissionController::class, 'update'])->name('missions.update');
        Route::post('missions/{mission}/nodes', [MissionNodeController::class, 'store'])->name('missions.nodes.store');
        Route::patch('missions/{mission}/nodes/{node}', [MissionNodeController::class, 'update'])
            ->name('missions.nodes.update');
        Route::delete('missions/{mission}/nodes/{node}', [MissionNodeController::class, 'destroy'])
            ->name('missions.nodes.destroy');
        Route::patch('missions/{mission}/nodes/{node}/position', [MissionNodeController::class, 'move'])
            ->name('missions.nodes.move');
    });
    Route::inertia('student', 'student/Dashboard')
        ->middleware('role:student')
        ->name('student.dashboard');
});

require __DIR__.'/settings.php';
