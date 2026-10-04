<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mission_nodes', function (Blueprint $table) {
            $table->unsignedTinyInteger('coin_reward')->default(0)->after('pass_threshold');
        });

        Schema::create('mission_assignment_node_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('mission_assignments')->cascadeOnDelete();
            $table->foreignId('node_id')->constrained('mission_nodes')->restrictOnDelete();
            $table->unsignedSmallInteger('experience_reward');
            $table->unsignedTinyInteger('coin_reward');
            $table->timestamps();

            $table->unique(['assignment_id', 'node_id']);
        });

        Schema::create('student_reward_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('node_id')->constrained('mission_nodes')->restrictOnDelete();
            $table->foreignId('first_progress_id')->unique()->constrained('node_progress')->restrictOnDelete();
            $table->unsignedSmallInteger('experience_awarded');
            $table->unsignedTinyInteger('coins_awarded');
            $table->timestamp('awarded_at');
            $table->timestamps();

            $table->unique(['student_id', 'node_id']);
            $table->index(['student_id', 'awarded_at']);
        });

        Schema::create('coin_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->integer('amount');
            $table->string('reason', 40);
            $table->foreignId('reward_grant_id')->nullable()->unique()
                ->constrained('student_reward_grants')->restrictOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
        });

        $now = now();
        DB::table('mission_assignments')
            ->orderBy('id')
            ->eachById(function (object $assignment) use ($now): void {
                $nodeIds = DB::table('mission_nodes')
                    ->where('mission_id', $assignment->mission_id)
                    ->pluck('id');

                foreach ($nodeIds as $nodeId) {
                    DB::table('mission_assignment_node_rewards')->insertOrIgnore([
                        'assignment_id' => $assignment->id,
                        'node_id' => $nodeId,
                        'experience_reward' => 10,
                        'coin_reward' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });

        DB::table('node_progress as progress')
            ->join('mission_enrollments as enrollments', 'enrollments.id', '=', 'progress.enrollment_id')
            ->select([
                'progress.id as progress_id',
                'progress.node_id',
                'progress.completed_at',
                'enrollments.student_id',
            ])
            ->orderBy('progress.id')
            ->chunkById(500, function ($progressRows) use ($now): void {
                foreach ($progressRows as $progress) {
                    DB::table('student_reward_grants')->insertOrIgnore([
                        'student_id' => $progress->student_id,
                        'node_id' => $progress->node_id,
                        'first_progress_id' => $progress->progress_id,
                        'experience_awarded' => 10,
                        'coins_awarded' => 0,
                        'awarded_at' => $progress->completed_at ?? $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }, 'progress.id', 'progress_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_ledger_entries');
        Schema::dropIfExists('student_reward_grants');
        Schema::dropIfExists('mission_assignment_node_rewards');

        Schema::table('mission_nodes', function (Blueprint $table) {
            $table->dropColumn('coin_reward');
        });
    }
};
