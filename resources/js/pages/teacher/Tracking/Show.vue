<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

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
};

defineProps<{
    classroom: { id: number; name: string; level: string; subject: string };
    assignment: {
        id: number;
        status: 'open' | 'closed';
        mission: { id: number; title: string; subject: string; level: string };
    };
    enrollments: Enrollment[];
}>();

const statusLabel = (status: Enrollment['status']): string =>
    ({
        not_started: 'Sin empezar',
        in_progress: 'En curso',
        completed: 'Completada',
    })[status];

const statusVariant = (
    status: Enrollment['status'],
): 'outline' | 'secondary' | 'default' =>
    status === 'completed'
        ? 'default'
        : status === 'in_progress'
          ? 'secondary'
          : 'outline';

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head :title="`Seguimiento de ${assignment.mission.title}`" />

    <main
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 p-4 md:p-8"
    >
        <Button as-child variant="ghost" class="w-fit">
            <Link href="/teacher/tracking"><ArrowLeft /> Seguimiento</Link>
        </Button>

        <section class="space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <Badge variant="outline">{{ classroom.name }}</Badge>
                <Badge variant="outline">{{ classroom.level }}</Badge>
                <Badge
                    :variant="
                        assignment.status === 'open' ? 'default' : 'secondary'
                    "
                >
                    {{
                        assignment.status === 'open'
                            ? 'asignación abierta'
                            : 'asignación cerrada'
                    }}
                </Badge>
            </div>
            <Heading
                :title="assignment.mission.title"
                description="Progreso, puntos, intentos y mejor nota se calculan desde la actividad guardada"
            />
        </section>

        <section v-if="enrollments.length" class="overflow-x-auto border-y">
            <table class="w-full min-w-[64rem] text-left text-sm">
                <thead class="border-b text-xs text-muted-foreground uppercase">
                    <tr>
                        <th class="px-3 py-3 font-medium">Alumno</th>
                        <th class="px-3 py-3 font-medium">Estado</th>
                        <th class="px-3 py-3 font-medium">Acceso</th>
                        <th class="px-3 py-3 font-medium">Progreso</th>
                        <th class="px-3 py-3 text-right font-medium">Puntos</th>
                        <th class="px-3 py-3 text-right font-medium">
                            Intentos
                        </th>
                        <th class="px-3 py-3 text-right font-medium">
                            Mejor quiz
                        </th>
                        <th class="px-3 py-3">
                            <span class="sr-only">Detalle</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="enrollment in enrollments" :key="enrollment.id">
                        <td class="px-3 py-4">
                            <p class="font-medium">
                                {{ enrollment.student.name }}
                            </p>
                            <p class="text-muted-foreground">
                                {{ enrollment.student.username }}
                            </p>
                        </td>
                        <td class="px-3 py-4">
                            <Badge :variant="statusVariant(enrollment.status)">
                                {{ statusLabel(enrollment.status) }}
                            </Badge>
                        </td>
                        <td class="px-3 py-4">
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    :variant="
                                        enrollment.membership_active
                                            ? 'outline'
                                            : 'secondary'
                                    "
                                >
                                    Matrícula
                                    {{
                                        enrollment.membership_active
                                            ? 'activa'
                                            : 'inactiva'
                                    }}
                                </Badge>
                                <Badge
                                    :variant="
                                        enrollment.enrollment_active
                                            ? 'outline'
                                            : 'secondary'
                                    "
                                >
                                    Inscripción
                                    {{
                                        enrollment.enrollment_active
                                            ? 'activa'
                                            : 'inactiva'
                                    }}
                                </Badge>
                                <Badge
                                    v-if="!enrollment.account_active"
                                    variant="destructive"
                                >
                                    Cuenta inactiva
                                </Badge>
                            </div>
                        </td>
                        <td class="min-w-52 px-3 py-4">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span>
                                    {{ enrollment.completed_nodes }} /
                                    {{ enrollment.total_nodes }}
                                </span>
                                <span>{{ enrollment.progress_percent }}%</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden bg-muted">
                                <div
                                    class="h-full bg-primary"
                                    :style="{
                                        width: `${enrollment.progress_percent}%`,
                                    }"
                                />
                            </div>
                        </td>
                        <td class="px-3 py-4 text-right font-medium">
                            {{ enrollment.points }}
                        </td>
                        <td class="px-3 py-4 text-right">
                            {{ enrollment.attempt_count }}
                        </td>
                        <td class="px-3 py-4 text-right">
                            {{
                                enrollment.best_score === null
                                    ? '—'
                                    : `${enrollment.best_score.toFixed(2)}%`
                            }}
                        </td>
                        <td class="px-3 py-4 text-right">
                            <Button as-child variant="outline" size="sm">
                                <Link
                                    :href="`/teacher/tracking/classes/${classroom.id}/assignments/${assignment.id}/enrollments/${enrollment.id}`"
                                >
                                    Detalle <ArrowRight />
                                </Link>
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <p v-else class="border-y py-6 text-sm text-muted-foreground">
            Esta asignación no tiene inscripciones actuales ni históricas.
        </p>
    </main>
</template>
