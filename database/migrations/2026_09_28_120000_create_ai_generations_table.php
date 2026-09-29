<?php

use App\Enums\AiGenerationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mission_nodes', function (Blueprint $table) {
            $table->boolean('review_required')->default(false)->after('pass_threshold');
            $table->text('review_note')->nullable()->after('review_required');
        });

        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('request_token')->unique();
            $table->enum('status', array_column(AiGenerationStatus::cases(), 'value'))
                ->default(AiGenerationStatus::Processing->value);
            $table->json('input_summary');
            $table->json('output_json')->nullable();
            $table->string('error_code')->nullable();
            $table->string('provider_response_id')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'created_at']);
            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');

        Schema::table('mission_nodes', function (Blueprint $table) {
            $table->dropColumn(['review_required', 'review_note']);
        });
    }
};
