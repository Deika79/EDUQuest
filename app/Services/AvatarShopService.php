<?php

namespace App\Services;

use App\Models\AvatarProfile;
use App\Models\ClassroomMembership;
use App\Models\CoinLedgerEntry;
use App\Models\CosmeticItem;
use App\Models\StudentCosmeticItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AvatarShopService
{
    public function __construct(private readonly StudentRewardService $rewards) {}

    public function grantStarter(User $student, AvatarProfile $profile): StudentCosmeticItem
    {
        $starter = CosmeticItem::query()
            ->where('character_key', $profile->character_key)
            ->where('starter', true)
            ->where('active', true)
            ->first();

        if ($starter === null) {
            throw ValidationException::withMessages([
                'character_key' => 'El catálogo inicial no está preparado. Ejecuta el seeder del catálogo.',
            ]);
        }

        $ownership = new StudentCosmeticItem([
            'acquisition_type' => StudentCosmeticItem::ACQUISITION_STARTER,
            'acquired_at' => now(),
        ]);
        $ownership->student()->associate($student);
        $ownership->cosmeticItem()->associate($starter);
        $ownership->save();

        $profile->equippedAppearance()->associate($starter);
        $profile->save();

        return $ownership;
    }

    /** @return array{ownership: StudentCosmeticItem, purchased: bool} */
    public function purchase(User $student, CosmeticItem $item): array
    {
        return DB::transaction(function () use ($student, $item): array {
            $lockedStudent = User::query()->lockForUpdate()->findOrFail($student->id);
            $profile = AvatarProfile::query()
                ->where('student_id', $lockedStudent->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedItem = CosmeticItem::query()->lockForUpdate()->findOrFail($item->id);

            $this->assertPurchasable($lockedStudent, $profile, $lockedItem);

            $existing = StudentCosmeticItem::query()
                ->where('student_id', $lockedStudent->id)
                ->where('cosmetic_item_id', $lockedItem->id)
                ->first();

            if ($existing !== null) {
                return ['ownership' => $existing, 'purchased' => false];
            }

            $summary = $this->rewards->summary($lockedStudent);

            if ($summary['level'] < $lockedItem->minimum_level) {
                throw ValidationException::withMessages([
                    'item' => "Necesitas alcanzar el nivel {$lockedItem->minimum_level}.",
                ]);
            }

            if ($summary['coins'] < $lockedItem->coin_price) {
                throw ValidationException::withMessages([
                    'item' => 'No tienes monedas suficientes para esta apariencia.',
                ]);
            }

            $ownership = new StudentCosmeticItem([
                'acquisition_type' => StudentCosmeticItem::ACQUISITION_PURCHASE,
                'acquired_at' => now(),
            ]);
            $ownership->student()->associate($lockedStudent);
            $ownership->cosmeticItem()->associate($lockedItem);
            $ownership->save();

            $entry = new CoinLedgerEntry([
                'amount' => -$lockedItem->coin_price,
                'reason' => CoinLedgerEntry::REASON_COSMETIC_PURCHASE,
            ]);
            $entry->student()->associate($lockedStudent);
            $entry->cosmeticItem()->associate($lockedItem);
            $entry->save();

            return ['ownership' => $ownership, 'purchased' => true];
        }, 3);
    }

    public function equip(User $student, StudentCosmeticItem $ownership): AvatarProfile
    {
        return DB::transaction(function () use ($student, $ownership): AvatarProfile {
            $lockedStudent = User::query()->lockForUpdate()->findOrFail($student->id);
            $profile = AvatarProfile::query()
                ->where('student_id', $lockedStudent->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedOwnership = StudentCosmeticItem::query()
                ->with('cosmeticItem')
                ->lockForUpdate()
                ->findOrFail($ownership->id);

            abort_unless(
                $lockedStudent->isStudent()
                && $lockedStudent->active
                && $lockedOwnership->student_id === $lockedStudent->id,
                403,
            );

            if ($lockedOwnership->cosmeticItem->character_key !== $profile->character_key) {
                throw ValidationException::withMessages([
                    'item' => 'Esta apariencia no corresponde a tu personaje.',
                ]);
            }

            $profile->equippedAppearance()->associate($lockedOwnership->cosmeticItem);
            $profile->save();

            return $profile;
        }, 3);
    }

    private function assertPurchasable(User $student, AvatarProfile $profile, CosmeticItem $item): void
    {
        abort_unless($student->isStudent() && $student->active, 403);

        if (! $item->active || $item->starter || $item->character_key !== $profile->character_key) {
            throw ValidationException::withMessages([
                'item' => 'Esta apariencia no está disponible para tu personaje.',
            ]);
        }

        $hasActiveMembership = ClassroomMembership::query()
            ->where('student_id', $student->id)
            ->where('active', true)
            ->exists();

        if (! $hasActiveMembership) {
            throw ValidationException::withMessages([
                'item' => 'Necesitas una matrícula activa para comprar apariencias.',
            ]);
        }
    }
}
