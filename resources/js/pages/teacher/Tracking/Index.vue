<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BarChart3 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';

type Assignment = {
    id: number;
    status: 'open' | 'closed';
    enrollments_count: number;
    mission: { id: number; title: string; subject: string; level: string };
};

type Classroom = {
    id: number;
    name: string;
    level: string;
    subject: string;
    assignments: Assignment[];
};

const props = defineProps<{ classrooms: Classroom[] }>();
const classroomId = ref<number | null>(props.classrooms[0]?.id ?? null);
const classroom = computed(
    () =>
        props.classrooms.find((item) => item.id === classroomId.value) ?? null,
);
const assignmentId = ref<number | null>(
    classroom.value?.assignments[0]?.id ?? null,
);
const assignment = computed(
    () =>
        classroom.value?.assignments.find(
            (item) => item.id === assignmentId.value,
        ) ?? null,
);

watch(classroomId, () => {
    assignmentId.value = classroom.value?.assignments[0]?.id ?? null;
});

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Tracking" />

    <main
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 p-4 md:p-8"
    >
        <Heading
            title="Student tracking"
            description="Compare progress for a mission assigned to one of your classes"
        />

        <section v-if="classrooms.length" class="space-y-6 border-y py-6">
            <div class="grid gap-5 md:grid-cols-2">
                <div class="space-y-2">
                    <Label for="tracking-classroom">Class</Label>
                    <select
                        id="tracking-classroom"
                        v-model="classroomId"
                        class="h-9 w-full border bg-background px-3 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option
                            v-for="item in classrooms"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ item.name }} · {{ item.level }} ·
                            {{ item.subject }}
                        </option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="tracking-assignment">Assigned mission</Label>
                    <select
                        id="tracking-assignment"
                        v-model="assignmentId"
                        :disabled="!classroom?.assignments.length"
                        class="h-9 w-full border bg-background px-3 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:opacity-50"
                    >
                        <option
                            v-for="item in classroom?.assignments ?? []"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ item.mission.title }} · {{ item.status }}
                        </option>
                    </select>
                </div>
            </div>

            <div
                v-if="assignment && classroom"
                class="flex flex-col gap-4 border-t pt-5 sm:flex-row sm:items-center"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium">
                            {{ assignment.mission.title }}
                        </p>
                        <Badge
                            :variant="
                                assignment.status === 'open'
                                    ? 'default'
                                    : 'secondary'
                            "
                        >
                            {{ assignment.status }}
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ assignment.enrollments_count }} enrollment(s)
                    </p>
                </div>
                <Button as-child>
                    <Link
                        :href="`/teacher/tracking/classes/${classroom.id}/assignments/${assignment.id}`"
                    >
                        <BarChart3 /> View progress <ArrowRight />
                    </Link>
                </Button>
            </div>
            <p v-else class="border-t pt-5 text-sm text-muted-foreground">
                This class has no mission assignments to review.
            </p>
        </section>

        <p v-else class="border-y py-6 text-sm text-muted-foreground">
            Create a class before opening student tracking.
        </p>
    </main>
</template>
