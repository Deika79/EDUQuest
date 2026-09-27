<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Map } from '@lucide/vue';
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

defineProps<{ missions: Mission[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'My missions', href: dashboard() }] },
});
</script>

<template>
    <Head title="My missions" />
    <main
        class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-8 p-4 md:p-8"
    >
        <Heading
            title="My missions"
            description="Continue your assigned paths"
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
                            <Map /> Open map <ArrowRight />
                        </Link>
                    </Button>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between gap-4 text-sm">
                        <span
                            >{{ mission.completed_nodes }} of
                            {{ mission.total_nodes }} nodes</span
                        >
                        <span
                            >{{ mission.points }} points ·
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
            You have no active mission assignments.
        </p>

        <p class="text-sm text-muted-foreground">
            Questionnaires are not available in this milestone. Progress is
            saved after completing each available activity.
        </p>
    </main>
</template>
