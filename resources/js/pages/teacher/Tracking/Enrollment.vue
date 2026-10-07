<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, CircleDashed } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

type Attempt = {
    id: number;
    score: number;
    correct_answers: number;
    total_questions: number;
    passed: boolean;
    submitted_at: string;
};

type Node = {
    id: number;
    position: number;
    type: 'explanation' | 'video' | 'quiz' | 'flashcards';
    title: string;
    completed: boolean;
    completed_at: string | null;
    points: number;
    quiz: {
        attempt_count: number;
        best_score: number | null;
        ever_passed: boolean;
        attempts: Attempt[];
    } | null;
};

type Enrollment = {
    id: number;
    student: { name: string; username: string };
    account_active: boolean;
    membership_active: boolean;
    enrollment_active: boolean;
    status: 'not_started' | 'in_progress' | 'completed';
    completed_nodes: number;
    total_nodes: number;
    progress_percent: number;
    points: number;
    attempt_count: number;
    best_score: number | null;
    nodes: Node[];
};

defineProps<{
    classroom: { id: number; name: string; level: string; subject: string };
    assignment: {
        id: number;
        status: 'open' | 'closed';
        mission: { id: number; title: string; subject: string; level: string };
    };
    enrollment: Enrollment;
}>();

const statusLabel = (status: Enrollment['status']): string =>
    ({
        not_started: 'Sin empezar',
        in_progress: 'En curso',
        completed: 'Completada',
    })[status];

const typeLabels: Record<Node['type'], string> = {
    explanation: 'Explicación',
    video: 'Vídeo',
    quiz: 'Cuestionario',
    flashcards: 'Flashcards',
};

const formatDate = (value: string): string =>
    new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head :title="`Progreso de ${enrollment.student.name}`" />

    <main
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 md:p-8"
    >
        <Button as-child variant="ghost" class="w-fit">
            <Link
                :href="`/teacher/tracking/classes/${classroom.id}/assignments/${assignment.id}`"
            >
                <ArrowLeft /> Progreso de la clase
            </Link>
        </Button>

        <section class="space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <Badge variant="outline">{{ classroom.name }}</Badge>
                <Badge variant="outline">{{ assignment.mission.title }}</Badge>
                <Badge>{{ statusLabel(enrollment.status) }}</Badge>
            </div>
            <Heading
                :title="enrollment.student.name"
                :description="enrollment.student.username"
            />
            <div class="flex flex-wrap gap-2">
                <Badge
                    :variant="
                        enrollment.membership_active ? 'outline' : 'secondary'
                    "
                >
                    Matrícula
                    {{ enrollment.membership_active ? 'activa' : 'inactiva' }}
                </Badge>
                <Badge
                    :variant="
                        enrollment.enrollment_active ? 'outline' : 'secondary'
                    "
                >
                    Inscripción
                    {{ enrollment.enrollment_active ? 'activa' : 'inactiva' }}
                </Badge>
                <Badge v-if="!enrollment.account_active" variant="destructive">
                    Cuenta inactiva
                </Badge>
            </div>
        </section>

        <dl class="grid border-y sm:grid-cols-4 sm:divide-x">
            <div class="p-4">
                <dt class="text-sm text-muted-foreground">Progreso</dt>
                <dd class="mt-1 text-xl font-semibold">
                    {{ enrollment.progress_percent }}%
                </dd>
                <dd class="text-sm text-muted-foreground">
                    {{ enrollment.completed_nodes }} de
                    {{ enrollment.total_nodes }} etapas
                </dd>
            </div>
            <div class="p-4">
                <dt class="text-sm text-muted-foreground">Puntos</dt>
                <dd class="mt-1 text-xl font-semibold">
                    {{ enrollment.points }}
                </dd>
            </div>
            <div class="p-4">
                <dt class="text-sm text-muted-foreground">Intentos de quiz</dt>
                <dd class="mt-1 text-xl font-semibold">
                    {{ enrollment.attempt_count }}
                </dd>
            </div>
            <div class="p-4">
                <dt class="text-sm text-muted-foreground">
                    Mejor nota de quiz
                </dt>
                <dd class="mt-1 text-xl font-semibold">
                    {{
                        enrollment.best_score === null
                            ? '—'
                            : `${enrollment.best_score.toFixed(2)}%`
                    }}
                </dd>
            </div>
        </dl>

        <section class="space-y-5">
            <Heading
                variant="small"
                title="Etapas de la misión"
                description="Nodos completados e historial de cuestionarios de esta inscripción"
            />

            <div class="divide-y border-y">
                <article
                    v-for="node in enrollment.nodes"
                    :key="node.id"
                    class="space-y-4 py-5"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                        <component
                            :is="node.completed ? CheckCircle2 : CircleDashed"
                            class="mt-0.5 size-5 shrink-0"
                            :class="
                                node.completed
                                    ? 'text-green-700'
                                    : 'text-muted-foreground'
                            "
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">
                                    {{ node.position }}. {{ node.title }}
                                </span>
                                <Badge variant="outline">{{
                                    typeLabels[node.type]
                                }}</Badge>
                                <Badge
                                    :variant="
                                        node.completed ? 'default' : 'secondary'
                                    "
                                >
                                    {{
                                        node.completed
                                            ? 'Completada'
                                            : 'Pendiente'
                                    }}
                                </Badge>
                            </div>
                            <p
                                v-if="node.completed_at"
                                class="mt-1 text-sm text-muted-foreground"
                            >
                                {{ node.points }} puntos · completada
                                {{ formatDate(node.completed_at) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="node.quiz" class="space-y-3 pl-0 sm:pl-8">
                        <div class="flex flex-wrap gap-2 text-sm">
                            <Badge variant="outline">
                                {{ node.quiz.attempt_count }} intento(s)
                            </Badge>
                            <Badge variant="outline">
                                Mejor:
                                {{
                                    node.quiz.best_score === null
                                        ? '—'
                                        : `${node.quiz.best_score.toFixed(2)}%`
                                }}
                            </Badge>
                            <Badge
                                :variant="
                                    node.quiz.ever_passed
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{
                                    node.quiz.ever_passed
                                        ? 'Aprobado'
                                        : 'No aprobado'
                                }}
                            </Badge>
                        </div>
                        <div
                            v-if="node.quiz.attempts.length"
                            class="overflow-x-auto border-y"
                        >
                            <table
                                class="w-full min-w-[40rem] text-left text-sm"
                            >
                                <thead
                                    class="border-b text-xs text-muted-foreground uppercase"
                                >
                                    <tr>
                                        <th class="px-3 py-2 font-medium">
                                            Enviado
                                        </th>
                                        <th
                                            class="px-3 py-2 text-right font-medium"
                                        >
                                            Resultado
                                        </th>
                                        <th
                                            class="px-3 py-2 text-right font-medium"
                                        >
                                            Nota
                                        </th>
                                        <th
                                            class="px-3 py-2 text-right font-medium"
                                        >
                                            Respuestas
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="attempt in node.quiz.attempts"
                                        :key="attempt.id"
                                    >
                                        <td class="px-3 py-3">
                                            {{
                                                formatDate(attempt.submitted_at)
                                            }}
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <Badge
                                                :variant="
                                                    attempt.passed
                                                        ? 'default'
                                                        : 'destructive'
                                                "
                                            >
                                                {{
                                                    attempt.passed
                                                        ? 'Aprobado'
                                                        : 'No aprobado'
                                                }}
                                            </Badge>
                                        </td>
                                        <td
                                            class="px-3 py-3 text-right font-medium"
                                        >
                                            {{ attempt.score.toFixed(2) }}%
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            {{ attempt.correct_answers }} /
                                            {{ attempt.total_questions }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </main>
</template>
