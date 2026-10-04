<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Coins, Map, Sparkles, Trophy } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type Mission = {
    enrollment_id: number;
    title: string;
    description: string;
    subject: string;
    level: string;
    classroom: string;
    completed_nodes: number;
    total_nodes: number;
    progress_percent: number;
    points: number;
};

type Rewards = {
    experience: number;
    level: number;
    coins: number;
};

const props = defineProps<{ missions: Mission[]; rewards: Rewards }>();
const nextMission = computed(() => props.missions[0] ?? null);

defineOptions({
    layout: { breadcrumbs: [{ title: 'My missions', href: dashboard() }] },
});
</script>

<template>
    <Head title="Mis misiones" />
    <main
        class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-8 p-4 md:p-8"
    >
        <section
            class="relative min-h-56 overflow-hidden rounded-lg bg-[#101d36] text-white sm:min-h-64"
            aria-labelledby="student-cover-title"
        >
            <img
                src="/brand/portada-alumno-1280x448.webp"
                alt="Un camino luminoso conecta distintos mundos de aprendizaje"
                class="absolute inset-0 size-full object-cover object-center"
            />
            <div
                class="absolute inset-0 bg-[linear-gradient(90deg,rgba(7,18,35,0.96)_0%,rgba(7,18,35,0.74)_48%,rgba(7,18,35,0.14)_100%),linear-gradient(0deg,rgba(7,18,35,0.7),transparent_55%)]"
            />
            <div
                class="relative flex min-h-56 max-w-xl flex-col justify-end p-5 sm:min-h-64 sm:p-8"
            >
                <p
                    class="text-xs font-semibold tracking-[0.16em] text-[#f5b845] uppercase"
                >
                    Tu próxima misión
                </p>
                <template v-if="nextMission">
                    <h1
                        id="student-cover-title"
                        class="mt-2 text-2xl font-bold sm:text-3xl"
                    >
                        {{ nextMission.title }}
                    </h1>
                    <p class="mt-2 text-sm text-[#e8f0fb]">
                        {{ nextMission.completed_nodes }} de
                        {{ nextMission.total_nodes }} etapas ·
                        {{ nextMission.progress_percent }} % ·
                        {{ nextMission.points }} puntos
                    </p>
                    <Button
                        as-child
                        class="mt-5 w-fit bg-[#f5b845] text-[#101d36] hover:bg-[#ffd16d]"
                    >
                        <Link
                            :href="`/student/missions/${nextMission.enrollment_id}`"
                        >
                            <Map /> Continuar misión <ArrowRight />
                        </Link>
                    </Button>
                </template>
                <template v-else>
                    <h1
                        id="student-cover-title"
                        class="mt-2 text-2xl font-bold sm:text-3xl"
                    >
                        Te espera un mundo nuevo
                    </h1>
                    <p class="mt-2 text-sm text-[#e8f0fb]">
                        Cuando tu docente asigne una misión, aparecerá aquí.
                    </p>
                </template>
            </div>
        </section>

        <section aria-labelledby="rewards-title" class="border-y py-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <h2 id="rewards-title" class="font-semibold">
                        Tu recorrido
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        La experiencia marca tu nivel. Las monedas se guardan
                        por separado para futuras recompensas cosméticas.
                    </p>
                </div>
                <dl class="grid grid-cols-3 gap-2 text-center sm:min-w-80">
                    <div class="border px-3 py-3">
                        <dt
                            class="flex items-center justify-center gap-1 text-xs text-muted-foreground"
                        >
                            <Trophy class="size-4" aria-hidden="true" /> Nivel
                        </dt>
                        <dd class="mt-1 text-lg font-semibold">
                            {{ rewards.level }}
                        </dd>
                    </div>
                    <div class="border px-3 py-3">
                        <dt
                            class="flex items-center justify-center gap-1 text-xs text-muted-foreground"
                        >
                            <Sparkles class="size-4" aria-hidden="true" /> XP
                        </dt>
                        <dd class="mt-1 text-lg font-semibold">
                            {{ rewards.experience }}
                        </dd>
                    </div>
                    <div class="border px-3 py-3">
                        <dt
                            class="flex items-center justify-center gap-1 text-xs text-muted-foreground"
                        >
                            <Coins class="size-4" aria-hidden="true" /> Monedas
                        </dt>
                        <dd class="mt-1 text-lg font-semibold">
                            {{ rewards.coins }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <Heading
            title="Mis misiones"
            description="Continúa tus recorridos asignados"
        />

        <section v-if="missions.length" class="divide-y border-y">
            <article
                v-for="mission in missions"
                :key="mission.enrollment_id"
                class="space-y-4 py-6"
            >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold">{{ mission.title }}</h2>
                            <Badge variant="outline">{{
                                mission.subject
                            }}</Badge>
                            <Badge variant="secondary">{{
                                mission.level
                            }}</Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ mission.description }}
                        </p>
                        <p class="text-sm">{{ mission.classroom }}</p>
                    </div>
                    <Button as-child>
                        <Link
                            :href="`/student/missions/${mission.enrollment_id}`"
                        >
                            <Map /> Abrir mapa <ArrowRight />
                        </Link>
                    </Button>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between gap-4 text-sm">
                        <span
                            >{{ mission.completed_nodes }} de
                            {{ mission.total_nodes }} etapas</span
                        >
                        <span
                            >{{ mission.points }} puntos ·
                            {{ mission.progress_percent }}%</span
                        >
                    </div>
                    <div
                        class="h-2 overflow-hidden rounded-full bg-muted"
                        aria-hidden="true"
                    >
                        <div
                            class="h-full bg-primary"
                            :style="{ width: `${mission.progress_percent}%` }"
                        />
                    </div>
                </div>
            </article>
        </section>

        <p v-else class="border-y py-8 text-sm text-muted-foreground">
            No tienes misiones activas asignadas.
        </p>

        <p class="text-sm text-muted-foreground">
            Tu progreso se guarda al completar cada actividad. Los cuestionarios
            se corrigen en el servidor y puedes volver a intentarlos.
        </p>
    </main>
</template>
