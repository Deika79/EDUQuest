<?php

namespace App\Enums;

enum AiGenerationStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
