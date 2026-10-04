<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Check,
    Coins,
    LockKeyhole,
    Shirt,
    Sparkles,
    Trophy,
} from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type CatalogItem = {
    id: number;
    sku: string;
    name: string;
    collection: string;
    image: string;
    price: number;
    minimum_level: number;
    starter: boolean;
    owned: boolean;
    ownership_id: number | null;
    equipped: boolean;
    status:
        | 'Disponible'
        | 'Sin monedas'
        | 'Nivel insuficiente'
        | 'Sin matrícula activa'
        | 'Comprada';
    can_purchase: boolean;
};

defineProps<{
    profile: {
        character: string;
        equipped_name: string | null;
        equipped_image: string | null;
        has_active_membership: boolean;
    };
    rewards: { experience: number; level: number; coins: number };
    catalog: CatalogItem[];
}>();

const processing = ref<string | null>(null);

const purchase = (item: CatalogItem) => {
    processing.value = `purchase-${item.id}`;
    router.post(
        `/student/avatar/catalog/${item.id}/purchase`,
        {},
        {
            preserveScroll: true,
            onFinish: () => (processing.value = null),
        },
    );
};

const equip = (item: CatalogItem) => {
    if (!item.ownership_id) return;

    processing.value = `equip-${item.id}`;
    router.patch(
        `/student/avatar/inventory/${item.ownership_id}/equip`,
        {},
        {
            preserveScroll: true,
            onFinish: () => (processing.value = null),
        },
    );
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Avatar y tienda', href: '/student/avatar' }],
    },
});
</script>

<template>
    <Head title="Avatar y tienda" />

    <main
        class="mx-auto w-full max-w-6xl min-w-0 space-y-8 overflow-x-clip p-4 sm:p-6"
    >
        <Heading
            title="Avatar y tienda"
            description="Elige qué apariencia de tu personaje quieres llevar"
        />

        <section
            aria-labelledby="equipped-title"
            class="grid gap-6 border-y py-6 sm:grid-cols-[minmax(180px,260px)_1fr] sm:items-center"
        >
            <div
                class="mx-auto aspect-[2/3] w-full max-w-52 overflow-hidden bg-muted"
            >
                <img
                    v-if="profile.equipped_image"
                    :src="profile.equipped_image"
                    :alt="profile.equipped_name ?? 'Apariencia equipada'"
                    class="h-full w-full object-contain"
                />
            </div>
            <div class="min-w-0 space-y-5">
                <div>
                    <p class="text-sm text-muted-foreground">
                        Apariencia equipada
                    </p>
                    <h2 id="equipped-title" class="text-2xl font-semibold">
                        {{ profile.equipped_name ?? 'Apariencia inicial' }}
                    </h2>
                </div>
                <dl
                    class="grid min-w-0 grid-cols-1 gap-2 text-center min-[420px]:grid-cols-3"
                >
                    <div class="border px-2 py-3">
                        <dt
                            class="flex items-center justify-center gap-1 text-xs text-muted-foreground"
                        >
                            <Trophy class="size-4" aria-hidden="true" /> Nivel
                        </dt>
                        <dd class="mt-1 font-semibold">{{ rewards.level }}</dd>
                    </div>
                    <div class="border px-2 py-3">
                        <dt
                            class="flex items-center justify-center gap-1 text-xs text-muted-foreground"
                        >
                            <Sparkles class="size-4" aria-hidden="true" /> XP
                        </dt>
                        <dd class="mt-1 font-semibold">
                            {{ rewards.experience }}
                        </dd>
                    </div>
                    <div class="border px-2 py-3">
                        <dt
                            class="flex items-center justify-center gap-1 text-xs text-muted-foreground"
                        >
                            <Coins class="size-4" aria-hidden="true" /> Monedas
                        </dt>
                        <dd class="mt-1 font-semibold">{{ rewards.coins }}</dd>
                    </div>
                </dl>
                <p
                    v-if="!profile.has_active_membership"
                    class="text-sm text-destructive"
                >
                    Necesitas una matrícula activa para comprar. Tu inventario y
                    saldo se conservan.
                </p>
            </div>
        </section>

        <section aria-labelledby="catalog-title" class="space-y-4">
            <div>
                <h2 id="catalog-title" class="text-xl font-semibold">
                    Apariencias
                </h2>
                <p class="text-sm text-muted-foreground">
                    Subir de nivel permite comprar; no regala ni equipa
                    apariencias automáticamente.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="item in catalog"
                    :key="item.sku"
                    class="border bg-background"
                >
                    <div class="aspect-[2/3] overflow-hidden bg-muted">
                        <img
                            :src="item.image"
                            :alt="item.name"
                            class="h-full w-full object-contain"
                            loading="lazy"
                        />
                    </div>
                    <div class="space-y-4 p-4">
                        <div
                            class="flex flex-wrap items-start justify-between gap-2"
                        >
                            <div>
                                <h3 class="font-semibold">{{ item.name }}</h3>
                                <p
                                    class="text-sm text-muted-foreground capitalize"
                                >
                                    {{ item.collection }}
                                </p>
                            </div>
                            <Badge
                                :variant="item.equipped ? 'default' : 'outline'"
                            >
                                {{ item.equipped ? 'Equipada' : item.status }}
                            </Badge>
                        </div>

                        <dl class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <dt class="text-muted-foreground">Precio</dt>
                                <dd class="flex items-center gap-1 font-medium">
                                    <Coins class="size-4" aria-hidden="true" />
                                    {{ item.starter ? 'Incluida' : item.price }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Requisito</dt>
                                <dd class="font-medium">
                                    Nivel {{ item.minimum_level }}
                                </dd>
                            </div>
                        </dl>

                        <Button
                            v-if="item.owned && !item.equipped"
                            class="w-full"
                            :disabled="processing !== null"
                            @click="equip(item)"
                        >
                            <Shirt aria-hidden="true" /> Equipar
                        </Button>
                        <Button
                            v-else-if="!item.owned"
                            class="w-full"
                            :variant="
                                item.can_purchase ? 'default' : 'secondary'
                            "
                            :disabled="
                                !item.can_purchase || processing !== null
                            "
                            @click="purchase(item)"
                        >
                            <Coins
                                v-if="item.can_purchase"
                                aria-hidden="true"
                            />
                            <LockKeyhole v-else aria-hidden="true" />
                            {{
                                item.can_purchase
                                    ? `Comprar por ${item.price}`
                                    : item.status
                            }}
                        </Button>
                        <Button
                            v-else
                            class="w-full"
                            variant="secondary"
                            disabled
                        >
                            <Check aria-hidden="true" /> Equipada
                        </Button>
                    </div>
                </article>
            </div>
        </section>

        <p class="text-sm text-muted-foreground">
            Las apariencias son exclusivamente estéticas. No cambian preguntas,
            notas, puntos, progreso ni desbloqueos de misiones.
        </p>
    </main>
</template>
