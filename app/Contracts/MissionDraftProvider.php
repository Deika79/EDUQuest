<?php

namespace App\Contracts;

use App\Data\ProviderMissionDraft;

interface MissionDraftProvider
{
    public function isConfigured(): bool;

    /** @param array<string, mixed> $input */
    public function generate(array $input): ProviderMissionDraft;
}
