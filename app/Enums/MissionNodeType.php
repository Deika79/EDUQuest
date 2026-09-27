<?php

namespace App\Enums;

enum MissionNodeType: string
{
    case Explanation = 'explanation';
    case Video = 'video';
    case Quiz = 'quiz';
    case Flashcards = 'flashcards';
}
