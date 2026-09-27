<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('mission_enrollments')->cascadeOnDelete();
            $table->foreignId('node_id')->constrained('mission_nodes')->restrictOnDelete();
            $table->decimal('score', 9, 6);
            $table->unsignedTinyInteger('correct_answers');
            $table->unsignedTinyInteger('total_questions');
            $table->boolean('passed');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['enrollment_id', 'node_id', 'submitted_at']);
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('quiz_questions')->restrictOnDelete();
            $table->foreignId('option_id')->constrained('quiz_options')->restrictOnDelete();
            $table->boolean('is_correct');
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_attempts');
    }
};
