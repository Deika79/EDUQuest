<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, CircleAlert } from '@lucide/vue';
import TeacherMissionController from '@/actions/App/Http/Controllers/Teacher/TeacherMissionController';
import MissionNodeEditor, {
    type MissionNode,
} from '@/components/missions/MissionNodeEditor.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
    status: 'draft';
};

defineProps<{
    mission: Mission;
    nodes: MissionNode[];
    readiness: { ready: boolean; errors: string[] };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Teacher', href: dashboard() },
            { title: 'Mission drafts', href: '/teacher/missions' },
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
                <Link href="/teacher/missions">
                    <ArrowLeft />
                    Mission drafts
                </Link>
            </Button>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <Heading
                    :title="mission.title"
                    description="Draft mission editor"
                />
                <Badge variant="outline">Draft</Badge>
            </div>

            <Form
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
                    >
                        Save mission details
                    </Button>
                </div>
            </Form>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Draft readiness"
                description="Validation only; publishing is not enabled"
            />
            <div
                v-if="readiness.ready"
                class="flex items-center gap-3 border-y py-5 text-sm"
            >
                <CheckCircle2 class="size-5 text-green-600" />
                All saved nodes are complete and the draft would be ready to
                publish.
            </div>
            <div v-else class="space-y-3 border-y py-5">
                <div class="flex items-center gap-3 text-sm font-medium">
                    <CircleAlert class="size-5 text-amber-600" />
                    Draft not ready
                </div>
                <ul
                    class="list-disc space-y-1 pl-6 text-sm text-muted-foreground"
                >
                    <li v-for="error in readiness.errors" :key="error">
                        {{ error }}
                    </li>
                </ul>
            </div>
        </section>

        <section class="space-y-6">
            <Heading
                variant="small"
                title="Mission path"
                description="Saved in the displayed order"
            />
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
        </section>

        <p class="border-y py-5 text-sm text-muted-foreground">
            This draft is not visible to students. Publishing, assignment,
            activity completion, scoring, progress, and AI are pending.
        </p>
    </main>
</template>
