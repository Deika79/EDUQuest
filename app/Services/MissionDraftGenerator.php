<?php

namespace App\Services;

use App\Contracts\MissionDraftProvider;
use App\Enums\AiGenerationStatus;
use App\Enums\MissionNodeType;
use App\Enums\MissionSource;
use App\Enums\MissionStatus;
use App\Exceptions\AiGenerationException;
use App\Models\AiGeneration;
use App\Models\Mission;
use App\Models\MissionNode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class MissionDraftGenerator
{
    public function __construct(
        private readonly MissionDraftProvider $provider,
        private readonly AiMissionOutputValidator $validator,
    ) {}

    public function isConfigured(): bool
    {
        return $this->provider->isConfigured();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function generate(User $teacher, array $input, string $requestToken): Mission
    {
        if (! $this->isConfigured()) {
            throw new AiGenerationException(
                'not_configured',
                'La generacion asistida no esta configurada. Puedes seguir creando la mision manualmente.',
            );
        }

        $generation = $this->startGeneration($teacher, $input, $requestToken);

        if ($generation->mission_id !== null) {
            return $teacher->missions()->findOrFail($generation->mission_id);
        }

        try {
            $providerDraft = $this->provider->generate($input);
            $content = $this->validator->validate($providerDraft->content);

            if (count($content['nodes']) !== (int) $input['node_count']) {
                throw new AiGenerationException(
                    'invalid_output',
                    'El proveedor no respeto el numero de nodos solicitado. No se ha guardado ningun borrador.',
                );
            }
        } catch (ValidationException $exception) {
            $this->markFailed($generation, 'invalid_output');

            throw new AiGenerationException(
                'invalid_output',
                'La propuesta recibida no cumple las reglas de EDUQuest. No se ha guardado ningun borrador. Detalle: '
                    .implode(', ', array_keys($exception->errors())),
            );
        } catch (AiGenerationException $exception) {
            $this->markFailed($generation, $exception->errorCode);

            throw $exception;
        } catch (Throwable $exception) {
            $this->markFailed($generation, 'unexpected_provider_error');

            report($exception);

            throw new AiGenerationException(
                'unexpected_provider_error',
                'No se pudo completar la generacion. No se ha guardado ningun borrador.',
            );
        }

        return DB::transaction(function () use ($generation, $teacher, $input, $content, $providerDraft): Mission {
            $generation->refresh();

            $mission = new Mission([
                'title' => $content['title'],
                'description' => $content['description'],
                'subject' => $input['subject'],
                'level' => $input['level'],
            ]);
            $mission->status = MissionStatus::Draft;
            $mission->source = MissionSource::Ai;
            $mission->teacher()->associate($teacher);
            $mission->save();

            foreach ($content['nodes'] as $index => $nodeData) {
                $this->createNode($mission, $index + 1, $nodeData);
            }

            $generation->mission()->associate($mission);
            $generation->fill([
                'status' => AiGenerationStatus::Completed,
                'output_json' => $content,
                'provider_response_id' => $providerDraft->responseId,
                'input_tokens' => $providerDraft->inputTokens,
                'output_tokens' => $providerDraft->outputTokens,
                'error_code' => null,
            ]);
            $generation->save();

            return $mission;
        });
    }

    /** @param array<string, mixed> $input */
    private function startGeneration(User $teacher, array $input, string $requestToken): AiGeneration
    {
        return DB::transaction(function () use ($teacher, $input, $requestToken): AiGeneration {
            User::query()->whereKey($teacher->id)->lockForUpdate()->firstOrFail();

            $existing = AiGeneration::query()->where('request_token', $requestToken)->first();

            if ($existing !== null) {
                if ($existing->teacher_id === $teacher->id
                    && $existing->status === AiGenerationStatus::Completed
                    && $existing->mission_id !== null) {
                    return $existing;
                }

                throw new AiGenerationException(
                    'duplicate_request',
                    'Esta solicitud ya se esta procesando o ya fue utilizada. Recarga el formulario antes de repetirla.',
                );
            }

            $staleBefore = now()->subSeconds((int) config('services.openai.timeout') + 30);
            $teacher->aiGenerations()
                ->where('status', AiGenerationStatus::Processing)
                ->where('created_at', '<', $staleBefore)
                ->update([
                    'status' => AiGenerationStatus::Failed,
                    'error_code' => 'interrupted',
                ]);

            if ($teacher->aiGenerations()->where('status', AiGenerationStatus::Processing)->exists()) {
                throw new AiGenerationException(
                    'generation_in_progress',
                    'Ya hay una generacion en curso para tu cuenta. Espera a que termine antes de iniciar otra.',
                );
            }

            $dailyLimit = max(1, (int) config('services.openai.daily_limit'));
            $usedToday = $teacher->aiGenerations()->where('created_at', '>=', now()->startOfDay())->count();

            if ($usedToday >= $dailyLimit) {
                throw new AiGenerationException(
                    'quota_exceeded',
                    "Has alcanzado el limite diario de {$dailyLimit} generaciones.",
                );
            }

            $generation = new AiGeneration([
                'request_token' => $requestToken,
                'status' => AiGenerationStatus::Processing,
                'input_summary' => $input,
            ]);
            $generation->teacher()->associate($teacher);
            $generation->save();

            return $generation;
        });
    }

    private function markFailed(AiGeneration $generation, string $errorCode): void
    {
        $generation->forceFill([
            'status' => AiGenerationStatus::Failed,
            'error_code' => $errorCode,
        ])->save();
    }

    /** @param array<string, mixed> $data */
    private function createNode(Mission $mission, int $position, array $data): void
    {
        $type = MissionNodeType::from($data['type']);
        $reviewRequired = $type === MissionNodeType::Video;
        $searchTerms = $reviewRequired ? (string) $data['video_search_terms'] : null;

        $node = new MissionNode([
            'position' => $position,
            'type' => $type,
            'title' => $data['title'],
            'body' => $type === MissionNodeType::Explanation ? $data['body'] : null,
            'pass_threshold' => $type === MissionNodeType::Quiz ? $data['pass_threshold'] : null,
            'coin_reward' => 0,
            'review_required' => $reviewRequired,
            'review_note' => $reviewRequired
                ? "Sugerencia de busqueda: {$searchTerms}. Selecciona y verifica un video antes de publicar."
                : null,
        ]);
        $node->mission()->associate($mission);
        $node->save();

        if ($type === MissionNodeType::Quiz) {
            foreach ($data['questions'] as $questionIndex => $questionData) {
                $question = $node->questions()->create([
                    'position' => $questionIndex + 1,
                    'statement' => $questionData['statement'],
                    'explanation' => $questionData['explanation'],
                ]);

                foreach ($questionData['options'] as $optionIndex => $optionData) {
                    $question->options()->create([
                        'position' => $optionIndex + 1,
                        'text' => $optionData['text'],
                        'is_correct' => $optionData['is_correct'],
                    ]);
                }
            }
        }

        if ($type === MissionNodeType::Flashcards) {
            foreach ($data['flashcards'] as $cardIndex => $cardData) {
                $node->flashcards()->create([
                    'position' => $cardIndex + 1,
                    'front' => $cardData['front'],
                    'back' => $cardData['back'],
                ]);
            }
        }
    }
}
