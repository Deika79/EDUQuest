<?php

namespace App\Services;

use App\Enums\MissionNodeType;
use App\Enums\VideoProvider;
use App\Models\Mission;
use App\Models\MissionNode;
use App\Support\VideoReference;
use Illuminate\Support\Facades\DB;

class MissionNodeWriter
{
    /** @param array<string, mixed> $data */
    public function save(Mission $mission, ?MissionNode $node, array $data): MissionNode
    {
        return DB::transaction(function () use ($mission, $node, $data): MissionNode {
            if ($node === null) {
                $lastPosition = (int) $mission->nodes()->lockForUpdate()->max('position');
                $node = new MissionNode(['position' => $lastPosition + 1]);
                $node->mission()->associate($mission);
            }

            $type = MissionNodeType::from($data['type']);
            $provider = isset($data['video_provider'])
                ? VideoProvider::tryFrom((string) $data['video_provider'])
                : null;

            $node->fill([
                'title' => $data['title'],
                'type' => $type,
                'body' => $type === MissionNodeType::Explanation ? $data['body'] : null,
                'video_provider' => $type === MissionNodeType::Video ? $provider : null,
                'video_id' => $type === MissionNodeType::Video
                    ? VideoReference::extractId($provider, (string) $data['video_reference'])
                    : null,
                'pass_threshold' => $type === MissionNodeType::Quiz ? $data['pass_threshold'] : null,
            ]);
            $node->save();

            $node->questions()->delete();
            $node->flashcards()->delete();

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

            return $node;
        });
    }
}
