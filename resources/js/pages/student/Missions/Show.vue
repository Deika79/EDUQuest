<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, LockKeyhole, Play } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type MapTheme = 'fantasy' | 'science' | 'old_west';

type MapAnchor = {
    x: number;
    y: number;
    label: string;
};

type Enrollment = {
    id: number;
    mission: {
        id: number;
        title: string;
        description: string;
        subject: string;
        level: string;
        map_theme: MapTheme;
    };
    classroom: { name: string };
    completed_nodes: number;
    total_nodes: number;
    progress_percent: number;
    points: number;
};

type MapNode = {
    id: number;
    position: number;
    type: 'explanation' | 'video' | 'quiz' | 'flashcards';
    title: string;
    status: 'completed' | 'available' | 'locked';
};

const props = defineProps<{ enrollment: Enrollment; nodes: MapNode[] }>();

const mapThemes: Record<
    MapTheme,
    { name: string; image: string; anchors: MapAnchor[] }
> = {
    fantasy: {
        name: 'Fantasía',
        image: '/brand/maps/fantasy_map.png',
        anchors: [
            { x: 18, y: 90, label: 'playa de inicio' },
            { x: 33, y: 83, label: 'puente de piedra' },
            { x: 47, y: 73, label: 'sendero del valle' },
            { x: 28, y: 61, label: 'biblioteca dorada' },
            { x: 43, y: 52, label: 'anfiteatro del río' },
            { x: 57, y: 43, label: 'isla circular' },
            { x: 73, y: 36, label: 'invernaderos de cristal' },
            { x: 83, y: 24, label: 'templo de hielo' },
            { x: 62, y: 15, label: 'jardines flotantes' },
            { x: 34, y: 16, label: 'observatorio arcano' },
            { x: 22, y: 34, label: 'ruinas del fósil' },
            { x: 45, y: 31, label: 'cascadas centrales' },
            { x: 66, y: 56, label: 'portal de cristal' },
            { x: 78, y: 66, label: 'mina violeta' },
            { x: 59, y: 80, label: 'arco del bosque' },
            { x: 42, y: 90, label: 'calas turquesa' },
        ],
    },
    science: {
        name: 'Ciencia ficción',
        image: '/brand/maps/science_map.png',
        anchors: [
            { x: 63, y: 90, label: 'puerto orbital' },
            { x: 49, y: 79, label: 'islas laboratorio' },
            { x: 69, y: 63, label: 'biodomos verdes' },
            { x: 55, y: 47, label: 'torre central' },
            { x: 42, y: 35, label: 'puente de energía' },
            { x: 27, y: 26, label: 'pirámide solar' },
            { x: 19, y: 12, label: 'observatorio polar' },
            { x: 51, y: 9, label: 'cúpula ártica' },
            { x: 70, y: 13, label: 'ciudad flotante' },
            { x: 83, y: 22, label: 'anillo orbital' },
            { x: 74, y: 39, label: 'cascadas suspendidas' },
            { x: 83, y: 52, label: 'ecosistema domo' },
            { x: 76, y: 72, label: 'plataforma marina' },
            { x: 43, y: 66, label: 'puente costero' },
            { x: 20, y: 62, label: 'sector neón' },
            { x: 24, y: 83, label: 'laboratorio volcánico' },
        ],
    },
    old_west: {
        name: 'Oeste',
        image: '/brand/maps/old_west_map.png',
        anchors: [
            { x: 48, y: 91, label: 'cañón de entrada' },
            { x: 61, y: 82, label: 'arco de roca' },
            { x: 46, y: 72, label: 'vías del desierto' },
            { x: 28, y: 64, label: 'mina iluminada' },
            { x: 42, y: 53, label: 'cascada del barranco' },
            { x: 61, y: 44, label: 'granja roja' },
            { x: 76, y: 37, label: 'pueblo fantasma' },
            { x: 72, y: 25, label: 'presa del río' },
            { x: 87, y: 12, label: 'ciudad de la meseta' },
            { x: 56, y: 18, label: 'lago de montaña' },
            { x: 33, y: 23, label: 'puente ferroviario' },
            { x: 17, y: 14, label: 'mina del bosque' },
            { x: 15, y: 36, label: 'estación del tren' },
            { x: 26, y: 39, label: 'calle principal' },
            { x: 40, y: 34, label: 'torre de agua' },
            { x: 55, y: 58, label: 'rancho del valle' },
        ],
    },
};

type PositionedNode = MapNode & {
    x: number;
    y: number;
    location: string;
};

const mapTheme = computed(() => mapThemes[props.enrollment.mission.map_theme]);

const positionedNodes = computed<PositionedNode[]>(() => {
    const anchors = mapTheme.value.anchors;
    const count = props.nodes.length;

    if (count === 0) {
        return [];
    }

    const offset = props.enrollment.mission.id % anchors.length;
    const rotated = [...anchors.slice(offset), ...anchors.slice(0, offset)];
    const route =
        Math.floor(props.enrollment.mission.id / anchors.length) % 2 === 0
            ? rotated
            : [...rotated].reverse();

    return props.nodes.map((node, index): PositionedNode => {
        if (count === 1) {
            const anchor = route[0];

            return { ...node, ...anchor, location: anchor.label };
        }

        const scaled = (index * (route.length - 1)) / (count - 1);
        const lower = Math.floor(scaled);
        const upper = Math.min(route.length - 1, Math.ceil(scaled));
        const ratio = scaled - lower;
        const start = route[lower];
        const end = route[upper];

        return {
            ...node,
            x: start.x + (end.x - start.x) * ratio,
            y: start.y + (end.y - start.y) * ratio,
            location:
                ratio === 0 ? start.label : `${start.label} → ${end.label}`,
        };
    });
});

const connectorPoints = computed(() =>
    positionedNodes.value.map((node) => `${node.x},${node.y}`).join(' '),
);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My missions', href: '/student/missions' }],
    },
});
</script>

<template>
    <Head :title="enrollment.mission.title" />
    <main
        class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-8 p-4 md:p-8"
    >
        <Button as-child variant="ghost" class="w-fit">
            <Link href="/student/missions"><ArrowLeft /> My missions</Link>
        </Button>

        <section class="space-y-4">
            <Heading
                :title="enrollment.mission.title"
                :description="enrollment.mission.description"
            />
            <div class="flex flex-wrap gap-2">
                <Badge variant="outline">{{ enrollment.classroom.name }}</Badge>
                <Badge variant="secondary">{{
                    enrollment.mission.subject
                }}</Badge>
                <Badge variant="secondary">{{
                    enrollment.mission.level
                }}</Badge>
            </div>
            <div class="space-y-2 border-y py-4">
                <div class="flex justify-between gap-4 text-sm">
                    <span
                        >{{ enrollment.completed_nodes }} of
                        {{ enrollment.total_nodes }} nodes</span
                    >
                    <span
                        >{{ enrollment.points }} points ·
                        {{ enrollment.progress_percent }}%</span
                    >
                </div>
                <div
                    class="h-2 overflow-hidden rounded-full bg-muted"
                    aria-hidden="true"
                >
                    <div
                        class="h-full bg-primary"
                        :style="{ width: `${enrollment.progress_percent}%` }"
                    />
                </div>
            </div>
        </section>

        <section class="space-y-5">
            <Heading
                variant="small"
                :title="`Mapa de ${mapTheme.name}`"
                description="Completa cada etapa para desbloquear la siguiente"
            />
            <div
                class="overflow-hidden rounded-lg border bg-slate-950 shadow-sm"
                :aria-label="`Mapa ilustrado de ${mapTheme.name} para ${enrollment.mission.title}`"
            >
                <div class="relative">
                    <img
                        :src="mapTheme.image"
                        :alt="`Escenario de ${mapTheme.name}`"
                        class="block h-auto w-full select-none"
                    />
                    <svg
                        class="pointer-events-none absolute inset-0 size-full"
                        viewBox="0 0 100 100"
                        preserveAspectRatio="none"
                        aria-hidden="true"
                    >
                        <polyline
                            v-if="positionedNodes.length > 1"
                            :points="connectorPoints"
                            fill="none"
                            stroke="rgba(15,23,42,0.72)"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            vector-effect="non-scaling-stroke"
                        />
                        <polyline
                            v-if="positionedNodes.length > 1"
                            :points="connectorPoints"
                            fill="none"
                            stroke="rgba(255,255,255,0.92)"
                            stroke-width="0.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            vector-effect="non-scaling-stroke"
                        />
                    </svg>
                    <component
                        :is="node.status === 'locked' ? 'button' : Link"
                        v-for="node in positionedNodes"
                        :key="node.id"
                        :href="
                            node.status === 'locked'
                                ? undefined
                                : `/student/missions/${enrollment.id}/nodes/${node.id}`
                        "
                        :disabled="node.status === 'locked'"
                        class="absolute flex min-h-11 min-w-11 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 text-sm font-bold shadow-lg transition focus-visible:ring-4 focus-visible:ring-white/90 focus-visible:outline-none"
                        :class="{
                            'border-white bg-emerald-600 text-white':
                                node.status === 'completed',
                            'border-white bg-amber-300 text-slate-950 hover:bg-amber-200':
                                node.status === 'available',
                            'cursor-not-allowed border-white/70 bg-slate-800/85 text-white/80':
                                node.status === 'locked',
                        }"
                        :style="{ left: `${node.x}%`, top: `${node.y}%` }"
                        :aria-label="`Etapa ${node.position}: ${node.title}. ${node.location}. Estado: ${node.status}`"
                    >
                        <CheckCircle2
                            v-if="node.status === 'completed'"
                            class="size-5"
                            aria-hidden="true"
                        />
                        <LockKeyhole
                            v-else-if="node.status === 'locked'"
                            class="size-5"
                            aria-hidden="true"
                        />
                        <span v-else>{{ node.position }}</span>
                    </component>
                </div>
            </div>

            <ol class="divide-y border-y">
                <li
                    v-for="node in positionedNodes"
                    :key="node.id"
                    class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge variant="outline">{{ node.position }}</Badge>
                            <h2 class="font-medium">{{ node.title }}</h2>
                            <Badge variant="secondary">{{ node.type }}</Badge>
                            <Badge
                                :variant="
                                    node.status === 'completed'
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{ node.status }}
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Lugar: {{ node.location }}
                        </p>
                    </div>
                    <Button
                        v-if="
                            node.status === 'available' ||
                            node.status === 'completed'
                        "
                        as-child
                        variant="outline"
                    >
                        <Link
                            :href="`/student/missions/${enrollment.id}/nodes/${node.id}`"
                        >
                            <Play />
                            {{
                                node.status === 'completed' ? 'Review' : 'Open'
                            }}
                        </Link>
                    </Button>
                </li>
            </ol>
        </section>

        <p class="border-y py-5 text-sm text-muted-foreground">
            Confirming reading, reviewing a video, or going through cards
            records your action. It does not prove comprehension or that a video
            was watched in full. Questionnaires are graded on the server and
            only unlock the next stage when passed.
        </p>
    </main>
</template>
