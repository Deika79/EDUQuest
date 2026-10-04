<?php

namespace Database\Seeders;

use App\Models\AvatarProfile;
use App\Models\CosmeticItem;
use App\Models\StudentCosmeticItem;
use Illuminate\Database\Seeder;

class CosmeticCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $item) {
            CosmeticItem::query()->updateOrCreate(['sku' => $item['sku']], $item);
        }

        AvatarProfile::query()->with('student')->eachById(function (AvatarProfile $profile): void {
            $starter = CosmeticItem::query()
                ->where('character_key', $profile->character_key)
                ->where('starter', true)
                ->sole();

            $ownership = StudentCosmeticItem::query()->firstOrCreate(
                [
                    'student_id' => $profile->student_id,
                    'cosmetic_item_id' => $starter->id,
                ],
                [
                    'acquisition_type' => StudentCosmeticItem::ACQUISITION_STARTER,
                    'acquired_at' => $profile->setup_completed_at,
                ],
            );

            if ($profile->equipped_cosmetic_item_id === null) {
                $profile->equippedAppearance()->associate($ownership->cosmeticItem()->firstOrFail());
                $profile->save();
            }
        });
    }

    /** @return list<array<string, int|string|bool>> */
    private function catalog(): array
    {
        return [
            $this->item('character-a', 'Personaje A', 'inicial', 0, 1, true, 1, 'personaje-a-nivel-1.webp'),
            $this->item('character-a', 'Personaje A', 'arcana', 6, 2, false, 2, 'personaje-a-nivel-2-arcano.webp'),
            $this->item('character-a', 'Personaje A', 'espacial', 12, 3, false, 3, 'personaje-a-nivel-3-espacial.webp'),
            $this->item('character-b', 'Personaje B', 'inicial', 0, 1, true, 1, 'personaje-b-nivel-1.webp'),
            $this->item('character-b', 'Personaje B', 'arcana', 6, 2, false, 2, 'personaje-b-nivel-2-arcano.webp'),
            $this->item('character-b', 'Personaje B', 'espacial', 12, 3, false, 3, 'personaje-b-nivel-3-espacial.webp'),
        ];
    }

    /** @return array<string, int|string|bool> */
    private function item(
        string $character,
        string $characterName,
        string $collection,
        int $price,
        int $level,
        bool $starter,
        int $order,
        string $file,
    ): array {
        return [
            'sku' => "{$character}-{$collection}",
            'name' => $collection === 'inicial'
                ? "{$characterName} · Inicial"
                : "{$characterName} · ".ucfirst($collection),
            'character_key' => $character,
            'collection' => $collection,
            'asset_path' => "/brand/avatars/assets/{$file}",
            'coin_price' => $price,
            'minimum_level' => $level,
            'starter' => $starter,
            'active' => true,
            'sort_order' => $order,
        ];
    }
}
