<?php

namespace App\Support;

final class AvatarOptions
{
    /** @var array<string, string> */
    public const CHARACTERS = [
        'character-a' => 'Personaje A',
        'character-b' => 'Personaje B',
    ];

    /** @var array<string, string> */
    private const INITIAL_IMAGES = [
        'character-a' => '/brand/avatars/assets/personaje-a-nivel-1.webp',
        'character-b' => '/brand/avatars/assets/personaje-b-nivel-1.webp',
    ];

    public static function initialImage(?string $characterKey): ?string
    {
        return $characterKey === null ? null : (self::INITIAL_IMAGES[$characterKey] ?? null);
    }

    /** @return list<array{key: string, label: string, image: string}> */
    public static function forFrontend(): array
    {
        return array_map(
            fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
                'image' => self::INITIAL_IMAGES[$key],
            ],
            self::CHARACTERS,
            array_keys(self::CHARACTERS),
        );
    }

    /** @return array{character_key: string} */
    public static function defaultSelection(): array
    {
        return [
            'character_key' => 'character-a',
        ];
    }
}
