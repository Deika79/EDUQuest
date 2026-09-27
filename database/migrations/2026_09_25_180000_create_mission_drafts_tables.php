<?php

use App\Enums\MissionNodeType;
use App\Enums\MissionSource;
use App\Enums\MissionStatus;
use App\Enums\VideoProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('subject');
            $table->string('level');
            $table->enum('status', array_column(MissionStatus::cases(), 'value'))
                ->default(MissionStatus::Draft->value);
            $table->enum('source', array_column(MissionSource::cases(), 'value'))
                ->default(MissionSource::Manual->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
        });

        Schema::create('mission_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->enum('type', array_column(MissionNodeType::cases(), 'value'));
            $table->string('title');
            $table->text('body')->nullable();
            $table->enum('video_provider', array_column(VideoProvider::cases(), 'value'))->nullable();
            $table->string('video_id')->nullable();
            $table->unsignedTinyInteger('pass_threshold')->nullable();
            $table->timestamps();

            $table->unique(['mission_id', 'position']);
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('mission_nodes')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->text('statement');
            $table->text('explanation');
            $table->timestamps();

            $table->unique(['node_id', 'position']);
        });

        Schema::create('quiz_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->text('text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['question_id', 'position']);
        });

        Schema::create('flashcards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('mission_nodes')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->text('front');
            $table->text('back');
            $table->timestamps();

            $table->unique(['node_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flashcards');
        Schema::dropIfExists('quiz_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('mission_nodes');
        Schema::dropIfExists('missions');
    }
};
