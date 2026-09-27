<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('mission_enrollments')->cascadeOnDelete();
            $table->foreignId('node_id')->constrained('mission_nodes')->restrictOnDelete();
            $table->timestamp('completed_at');
            $table->unsignedSmallInteger('points_awarded')->default(10);
            $table->timestamps();

            $table->unique(['enrollment_id', 'node_id']);
            $table->index(['node_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_progress');
    }
};
