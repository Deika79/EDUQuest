<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, LockKeyhole, Play } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Enrollment = {
    id: number;
    mission: {
        title: string;
        description: string;
        subject: string;
        level: string;
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

defineProps<{ enrollment: Enrollment; nodes: MapNode[] }>();

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
                title="Mission map"
                description="Complete each stage to unlock the next"
            />
            <ol
                class="relative space-y-4 before:absolute before:top-6 before:bottom-6 before:left-6 before:w-px before:bg-border"
            >
                <li
                    v-for="node in nodes"
                    :key="node.id"
                    class="relative flex min-h-20 items-center gap-4"
                >
                    <div
                        class="z-10 flex size-12 shrink-0 items-center justify-center rounded-full border bg-background font-semibold"
                        :class="{
                            'border-green-600 text-green-700':
                                node.status === 'completed',
                            'border-primary text-primary':
                                node.status === 'available',
                            'text-muted-foreground': node.status === 'locked',
                        }"
                    >
                        <CheckCircle2
                            v-if="node.status === 'completed'"
                            class="size-5"
                        />
                        <LockKeyhole
                            v-else-if="node.status === 'locked'"
                            class="size-5"
                        />
                        <span v-else>{{ node.position }}</span>
                    </div>
                    <div
                        class="flex min-w-0 flex-1 flex-col gap-3 border-y py-4 sm:flex-row sm:items-center"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-medium">{{ node.title }}</h2>
                                <Badge variant="outline">{{ node.type }}</Badge>
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
                                    node.status === 'completed'
                                        ? 'Review'
                                        : 'Open'
                                }}
                            </Link>
                        </Button>
                    </div>
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
