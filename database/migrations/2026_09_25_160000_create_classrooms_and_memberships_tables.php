<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('level');
            $table->string('subject');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'archived_at']);
        });

        Schema::create('classroom_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamp('activated_at');
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();

            $table->unique(['classroom_id', 'student_id']);
            $table->index(['student_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_memberships');
        Schema::dropIfExists('classrooms');
    }
};
