<?php

namespace App\Data;

final readonly class ProviderMissionDraft
{
    /** @param array<string, mixed> $content */
    public function __construct(
        public array $content,
        public ?string $responseId,
        public ?int $inputTokens,
        public ?int $outputTokens,
    ) {}
}
