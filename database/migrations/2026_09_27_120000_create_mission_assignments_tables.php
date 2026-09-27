<?php

use App\Enums\MissionAssignmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->enum('status', array_column(MissionAssignmentStatus::cases(), 'value'))
                ->default(MissionAssignmentStatus::Open->value);
            $table->timestamp('assigned_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['mission_id', 'classroom_id']);
            $table->index(['classroom_id', 'status']);
        });

        Schema::create('mission_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('mission_assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamp('activated_at');
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('activity_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
            $table->index(['student_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_enrollments');
        Schema::dropIfExists('mission_assignments');
    }
};
