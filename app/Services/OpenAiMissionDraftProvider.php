<?php

namespace App\Services;

use App\Contracts\MissionDraftProvider;
use App\Data\ProviderMissionDraft;
use App\Exceptions\AiGenerationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;

class OpenAiMissionDraftProvider implements MissionDraftProvider
{
    public function isConfigured(): bool
    {
        return filled(config('services.openai.api_key'));
    }

    /** @param array<string, mixed> $input */
    public function generate(array $input): ProviderMissionDraft
    {
        if (! $this->isConfigured()) {
            throw new AiGenerationException(
                'not_configured',
                'La generacion asistida no esta configurada. Puedes seguir creando la mision manualmente.',
            );
        }

        try {
            $response = Http::baseUrl((string) config('services.openai.base_url'))
                ->withToken((string) config('services.openai.api_key'))
                ->acceptJson()
                ->asJson()
                ->connectTimeout(5)
                ->timeout((int) config('services.openai.timeout'))
                ->post('/responses', $this->payload($input));
        } catch (ConnectionException) {
            throw new AiGenerationException(
                'provider_timeout',
                'El proveedor no respondio a tiempo. No se ha creado ningun borrador; prueba de nuevo mas tarde.',
            );
        }

        $this->ensureSuccessful($response);

        $text = $this->extractOutputText($response);

        try {
            $content = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new AiGenerationException(
                'invalid_output',
                'El proveedor devolvio una estructura no valida. No se ha guardado ningun borrador.',
            );
        }

        if (! is_array($content)) {
            throw new AiGenerationException(
                'invalid_output',
                'El proveedor devolvio una estructura no valida. No se ha guardado ningun borrador.',
            );
        }

        return new ProviderMissionDraft(
            content: $content,
            responseId: is_string($response->json('id')) ? $response->json('id') : null,
            inputTokens: is_int($response->json('usage.input_tokens'))
                ? $response->json('usage.input_tokens')
                : null,
            outputTokens: is_int($response->json('usage.output_tokens'))
                ? $response->json('usage.output_tokens')
                : null,
        );
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function payload(array $input): array
    {
        return [
            'model' => config('services.openai.model'),
            'store' => false,
            'max_output_tokens' => (int) config('services.openai.max_output_tokens'),
            'reasoning' => ['effort' => 'low'],
            'input' => [
                [
                    'role' => 'system',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => $this->systemPrompt(),
                    ]],
                ],
                [
                    'role' => 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => json_encode($input, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    ]],
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'eduquest_mission_draft',
                    'strict' => true,
                    'schema' => AiMissionSchema::get(),
                ],
            ],
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Eres un asistente de planificacion educativa. Devuelve una mision de repaso en espanol para EDUQuest.

Reglas:
1. Crea entre 4 y 8 nodos en un orden pedagogico lineal.
2. Usa explicaciones breves y actividades apropiadas al nivel indicado.
3. Incluye cuestionarios y flashcards cuando aporten valor. Cada pregunta tiene una sola opcion correcta y feedback explicativo.
4. Para un nodo de video, escribe solo terminos de busqueda en video_search_terms. Nunca inventes ni devuelvas URLs, IDs o afirmaciones de que un recurso esta verificado.
5. Usa null y arrays vacios en los campos que no correspondan al tipo de nodo.
6. No solicites ni incluyas nombres, cuentas, resultados ni otros datos personales del alumnado.
7. El resultado es un borrador para revision humana; no afirmes que esta publicado ni asignado.
PROMPT;
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful() && $response->json('status') === 'completed') {
            return;
        }

        if ($response->status() === 429) {
            throw new AiGenerationException(
                'provider_rate_limited',
                'El proveedor ha limitado temporalmente las solicitudes. Prueba de nuevo mas tarde.',
            );
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new AiGenerationException(
                'provider_authentication',
                'La configuracion del proveedor no es valida. La creacion manual sigue disponible.',
            );
        }

        throw new AiGenerationException(
            'provider_unavailable',
            'No se pudo completar la generacion con el proveedor. No se ha creado ningun borrador.',
        );
    }

    private function extractOutputText(Response $response): string
    {
        $output = $response->json('output');

        if (! is_array($output)) {
            throw new AiGenerationException('invalid_output', 'El proveedor no devolvio contenido util.');
        }

        foreach ($output as $item) {
            if (! is_array($item) || ! is_array($item['content'] ?? null)) {
                continue;
            }

            foreach ($item['content'] as $content) {
                if (is_array($content) && ($content['type'] ?? null) === 'refusal') {
                    throw new AiGenerationException(
                        'provider_refusal',
                        'El proveedor no pudo generar este contenido. Revisa las indicaciones o crea la mision manualmente.',
                    );
                }

                if (is_array($content)
                    && ($content['type'] ?? null) === 'output_text'
                    && is_string($content['text'] ?? null)) {
                    return $content['text'];
                }
            }
        }

        throw new AiGenerationException('invalid_output', 'El proveedor no devolvio contenido util.');
    }
}
