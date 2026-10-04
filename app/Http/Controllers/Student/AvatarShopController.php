<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\EquipCosmeticItemRequest;
use App\Http\Requests\Student\PurchaseCosmeticItemRequest;
use App\Models\ClassroomMembership;
use App\Models\CosmeticItem;
use App\Models\StudentCosmeticItem;
use App\Services\AvatarShopService;
use App\Services\StudentRewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AvatarShopController extends Controller
{
    public function index(Request $request, StudentRewardService $rewards): Response
    {
        Gate::authorize('viewAny', CosmeticItem::class);

        $student = $request->user();
        $profile = $student->avatarProfile()->with('equippedAppearance')->firstOrFail();
        $summary = $rewards->summary($student);
        $ownerships = $student->cosmeticItems()->get()->keyBy('cosmetic_item_id');
        $hasActiveMembership = ClassroomMembership::query()
            ->where('student_id', $student->id)
            ->where('active', true)
            ->exists();

        $catalog = CosmeticItem::query()
            ->where('character_key', $profile->character_key)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (CosmeticItem $item) use ($ownerships, $profile, $summary, $hasActiveMembership): array {
                $ownership = $ownerships->get($item->id);
                $owned = $ownership !== null;

                return [
                    'id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'collection' => $item->collection,
                    'image' => $item->asset_path,
                    'price' => $item->coin_price,
                    'minimum_level' => $item->minimum_level,
                    'starter' => $item->starter,
                    'owned' => $owned,
                    'ownership_id' => $ownership?->id,
                    'equipped' => $profile->equipped_cosmetic_item_id === $item->id,
                    'status' => $this->status($owned, $summary, $item, $hasActiveMembership),
                    'can_purchase' => ! $owned
                        && ! $item->starter
                        && $hasActiveMembership
                        && $summary['level'] >= $item->minimum_level
                        && $summary['coins'] >= $item->coin_price,
                ];
            });

        return Inertia::render('student/Avatar/Index', [
            'profile' => [
                'character' => $profile->character_key,
                'equipped_name' => $profile->equippedAppearance?->name,
                'equipped_image' => $profile->equippedAppearance?->asset_path,
                'has_active_membership' => $hasActiveMembership,
            ],
            'rewards' => $summary,
            'catalog' => $catalog,
        ]);
    }

    public function purchase(
        PurchaseCosmeticItemRequest $request,
        CosmeticItem $cosmeticItem,
        AvatarShopService $shop,
    ): RedirectResponse {
        $result = $shop->purchase($request->user(), $cosmeticItem);

        Inertia::flash('toast', [
            'type' => $result['purchased'] ? 'success' : 'info',
            'message' => $result['purchased']
                ? __('Apariencia comprada. Puedes equiparla cuando quieras.')
                : __('Esta apariencia ya estaba en tu inventario.'),
        ]);

        return to_route('student.avatar.index');
    }

    public function equip(
        EquipCosmeticItemRequest $request,
        StudentCosmeticItem $studentCosmeticItem,
        AvatarShopService $shop,
    ): RedirectResponse {
        $shop->equip($request->user(), $studentCosmeticItem);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Apariencia equipada.'),
        ]);

        return to_route('student.avatar.index');
    }

    /** @param array{experience: int, level: int, coins: int} $summary */
    private function status(bool $owned, array $summary, CosmeticItem $item, bool $hasActiveMembership): string
    {
        return match (true) {
            $owned => 'Comprada',
            ! $hasActiveMembership => 'Sin matrícula activa',
            $summary['level'] < $item->minimum_level => 'Nivel insuficiente',
            $summary['coins'] < $item->coin_price => 'Sin monedas',
            default => 'Disponible',
        };
    }
}
