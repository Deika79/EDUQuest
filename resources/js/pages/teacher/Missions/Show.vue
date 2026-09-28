<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    Archive,
    ArrowLeft,
    CheckCircle2,
    CircleAlert,
    Copy,
    Rocket,
    Send,
    XCircle,
} from '@lucide/vue';
import { computed } from 'vue';
import TeacherMissionController from '@/actions/App/Http/Controllers/Teacher/TeacherMissionController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import MissionNodeEditor, {
    type MissionNode,
} from '@/components/missions/MissionNodeEditor.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type Mission = {
    id: number;
    title: string;
    description: string;
    subject: string;
    level: string;
    status: 'draft' | 'published' | 'archived';
};

type Classroom = { id: number; name: string; level: string; subject: string };
type Assignment = {
    id: number;
    status: 'open' | 'closed';
    assigned_at: string;
    closed_at: string | null;
    classroom: Classroom;
    enrollments_count: number;
    active_enrollments_count: number;
};

const props = defineProps<{
    mission: Mission;
    nodes: MissionNode[];
    readiness: { ready: boolean; errors: string[] };
    classrooms: Classroom[];
    assignments: Assignment[];
}>();

const assignedClassroomIds = computed(
    () =>
        new Set(props.assignments.map((assignment) => assignment.classroom.id)),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Teacher', href: dashboard() },
            { title: 'Missions', href: '/teacher/missions' },
        ],
    },
});
</script>

<template>
    <Head :title="mission.title" />
    <main
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-10 p-4 md:p-8"
    >
        <section class="space-y-6">
            <Button as-child variant="ghost" class="w-fit">
                <Link href="/teacher/missions"><ArrowLeft /> Missions</Link>
            </Button>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <Heading
                    :title="mission.title"
                    :description="`${mission.subject} · ${mission.level}`"
                />
                <Badge
                    :variant="
                        mission.status === 'published'
                            ? 'default'
                            : mission.status === 'archived'
                              ? 'secondary'
                              : 'outline'
                    "
                >
                    {{ mission.status }}
                </Badge>
            </div>

            <Form
                v-if="mission.status === 'draft'"
                v-bind="TeacherMissionController.update.form(mission.id)"
                v-slot="{ errors, processing }"
                class="grid gap-4 border-y py-6 md:grid-cols-2"
            >
                <div class="grid gap-2 md:col-span-2">
                    <Label for="edit-mission-title">Title</Label>
                    <Input
                        id="edit-mission-title"
                        name="title"
                        :default-value="mission.title"
                        required
                    />
                    <InputError :message="errors.title" />
                </div>
                <div class="grid gap-2 md:col-span-2">
                    <Label for="edit-mission-description"
                        >Description and narrative context</Label
                    >
                    <textarea
                        id="edit-mission-description"
                        name="description"
                        :value="mission.description"
                        rows="4"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        required
                    />
                    <InputError :message="errors.description" />
                </div>
                <div class="grid gap-2">
                    <Label for="edit-mission-subject">Subject</Label>
                    <Input
                        id="edit-mission-subject"
                        name="subject"
                        :default-value="mission.subject"
                        required
                    />
                    <InputError :message="errors.subject" />
                </div>
                <div class="grid gap-2">
                    <Label for="edit-mission-level">Level</Label>
                    <Input
                        id="edit-mission-level"
                        name="level"
                        :default-value="mission.level"
                        required
                    />
                    <InputError :message="errors.level" />
                </div>
                <div class="md:col-span-2">
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                        >Save mission details</Button
                    >
                </div>
            </Form>
            <p v-else class="border-y py-5 text-sm text-muted-foreground">
                {{ mission.description }}
            </p>
        </section>

        <section v-if="mission.status === 'draft'" class="space-y-4">
            <Heading
                variant="small"
                title="Draft readiness"
                description="Every item must pass validation before publishing"
            />
            <div
                v-if="readiness.ready"
                class="flex items-center gap-3 border-y py-5 text-sm"
            >
                <CheckCircle2 class="size-5 text-green-600" /> This draft is
                ready to publish.
            </div>
            <div v-else class="space-y-3 border-y py-5">
                <div class="flex items-center gap-3 text-sm font-medium">
                    <CircleAlert class="size-5 text-amber-600" /> Draft not
                    ready
                </div>
                <ul
                    class="list-disc space-y-1 pl-6 text-sm text-muted-foreground"
                >
                    <li v-for="error in readiness.errors" :key="error">
                        {{ error }}
                    </li>
                </ul>
            </div>
            <Form
                :action="`/teacher/missions/${mission.id}/publish`"
                method="post"
                v-slot="{ errors, processing }"
                class="space-y-2"
            >
                <Button type="submit" :disabled="processing || !readiness.ready"
                    ><Rocket /> Publish mission</Button
                >
                <InputError :message="errors.mission" />
            </Form>
        </section>

        <section class="space-y-6">
            <Heading
                variant="small"
                title="Mission path"
                :description="
                    mission.status === 'draft'
                        ? 'Saved in the displayed order'
                        : 'Published content is read-only'
                "
            />
            <template v-if="mission.status === 'draft'">
                <MissionNodeEditor
                    v-for="(node, index) in nodes"
                    :key="node.id"
                    :mission-id="mission.id"
                    :node="node"
                    :first="index === 0"
                    :last="index === nodes.length - 1"
                />
                <div class="pt-4">
                    <Heading
                        variant="small"
                        title="Add node"
                        description="Choose one of the four activity types"
                    />
                    <MissionNodeEditor :mission-id="mission.id" />
                </div>
            </template>
            <div v-else class="divide-y border-y">
                <article
                    v-for="node in nodes"
                    :key="node.id"
                    class="space-y-3 py-5"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="outline">{{ node.position }}</Badge>
                        <h3 class="font-medium">{{ node.title }}</h3>
                        <Badge variant="secondary">{{ node.type }}</Badge>
                    </div>
                    <p
                        v-if="node.type === 'explanation'"
                        class="text-sm whitespace-pre-wrap text-muted-foreground"
                    >
                        {{ node.body }}
                    </p>
                    <p
                        v-else-if="node.type === 'video'"
                        class="text-sm text-muted-foreground"
                    >
                        {{ node.video_provider }} · {{ node.video_reference }}
                    </p>
                    <div v-else-if="node.type === 'quiz'" class="space-y-3">
                        <p class="text-sm text-muted-foreground">
                            Pass threshold: {{ node.pass_threshold }}%
                        </p>
                        <div
                            v-for="(question, questionIndex) in node.questions"
                            :key="question.id ?? questionIndex"
                            class="space-y-1 text-sm"
                        >
                            <p class="font-medium">{{ question.statement }}</p>
                            <p
                                v-for="(
                                    option, optionIndex
                                ) in question.options"
                                :key="option.id ?? optionIndex"
                                class="text-muted-foreground"
                            >
                                {{ option.is_correct ? 'Correct:' : 'Option:' }}
                                {{ option.text }}
                            </p>
                        </div>
                    </div>
                    <dl v-else class="grid gap-2 text-sm sm:grid-cols-2">
                        <div
                            v-for="(card, cardIndex) in node.flashcards"
                            :key="card.id ?? cardIndex"
                            class="border-l-2 pl-3"
                        >
                            <dt class="font-medium">{{ card.front }}</dt>
                            <dd class="text-muted-foreground">
                                {{ card.back }}
                            </dd>
                        </div>
                    </dl>
                </article>
            </div>
        </section>

        <section v-if="mission.status === 'published'" class="space-y-5">
            <Heading
                variant="small"
                title="Assign to classes"
                description="Active students receive an enrollment automatically"
            />
            <Form
                :action="`/teacher/missions/${mission.id}/assignments`"
                method="post"
                v-slot="{ errors, processing }"
                class="space-y-4 border-y py-5"
            >
                <div v-if="classrooms.length" class="grid gap-3 sm:grid-cols-2">
                    <label
                        v-for="classroom in classrooms"
                        :key="classroom.id"
                        class="flex items-start gap-3 text-sm"
                    >
                        <input
                            type="checkbox"
                            name="classroom_ids[]"
                            :value="classroom.id"
                            :disabled="assignedClassroomIds.has(classroom.id)"
                            class="mt-1 size-4"
                        />
                        <span>
                            <span class="block font-medium">{{
                                classroom.name
                            }}</span>
                            <span class="text-muted-foreground">
                                {{ classroom.level }} · {{ classroom.subject }}
                                <template
                                    v-if="
                                        assignedClassroomIds.has(classroom.id)
                                    "
                                >
                                    · Already assigned</template
                                >
                            </span>
                        </span>
                    </label>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    Create an active class before assigning this mission.
                </p>
                <InputError :message="errors.classroom_ids" />
                <Button
                    type="submit"
                    :disabled="processing || !classrooms.length"
                    ><Send /> Assign selected classes</Button
                >
            </Form>
        </section>

        <section v-if="assignments.length" class="space-y-4">
            <Heading
                variant="small"
                title="Assignments"
                description="Current distribution and enrollment status"
            />
            <div class="divide-y border-y">
                <div
                    v-for="assignment in assignments"
                    :key="assignment.id"
                    class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium">
                                {{ assignment.classroom.name }}
                            </p>
                            <Badge
                                :variant="
                                    assignment.status === 'open'
                                        ? 'default'
                                        : 'secondary'
                                "
                                >{{ assignment.status }}</Badge
                            >
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ assignment.active_enrollments_count }} active of
                            {{ assignment.enrollments_count }} enrollments
                        </p>
                    </div>
                    <Form
                        v-if="assignment.status === 'open'"
                        :action="`/teacher/missions/${mission.id}/assignments/${assignment.id}`"
                        method="delete"
                    >
                        <Button type="submit" variant="outline"
                            ><XCircle /> Withdraw or close</Button
                        >
                    </Form>
                </div>
            </div>
        </section>

        <section class="flex flex-wrap gap-3 border-y py-5">
            <Form
                v-if="mission.status === 'published'"
                :action="`/teacher/missions/${mission.id}/duplicate`"
                method="post"
            >
                <Button type="submit" variant="outline"
                    ><Copy /> Duplicate as draft</Button
                >
            </Form>
            <Form
                v-if="mission.status !== 'archived'"
                :action="`/teacher/missions/${mission.id}/archive`"
                method="post"
            >
                <Button type="submit" variant="outline"
                    ><Archive /> Archive mission</Button
                >
            </Form>
        </section>

        <p class="border-y py-5 text-sm text-muted-foreground">
            Student progress is available in Tracking. AI-assisted authoring is
            planned for a later milestone. Existing open assignments remain
            recorded when a mission is archived.
        </p>
    </main>
</template>
