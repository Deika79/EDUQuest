<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Map } from '@lucide/vue';
import TeacherMissionController from '@/actions/App/Http/Controllers/Teacher/TeacherMissionController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type MissionSummary = {
    id: number;
    title: string;
    description: string;
    subject: string;
    level: string;
    status: 'draft';
    nodes_count: number;
    updated_at: string;
};

defineProps<{ missions: MissionSummary[] }>();

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
    <Head title="Mission drafts" />
    <main
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-10 p-4 md:p-8"
    >
        <section class="space-y-6">
            <Heading
                title="Mission drafts"
                description="Manual mission editor"
            />
            <Form
                v-bind="TeacherMissionController.store.form()"
                :reset-on-success="['title', 'description', 'subject', 'level']"
                v-slot="{ errors, processing }"
                class="grid gap-4 border-y py-6 md:grid-cols-2"
            >
                <div class="grid gap-2 md:col-span-2">
                    <Label for="mission-title">Title</Label>
                    <Input id="mission-title" name="title" required />
                    <InputError :message="errors.title" />
                </div>
                <div class="grid gap-2 md:col-span-2">
                    <Label for="mission-description"
                        >Description and narrative context</Label
                    >
                    <textarea
                        id="mission-description"
                        name="description"
                        rows="4"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        required
                    />
                    <InputError :message="errors.description" />
                </div>
                <div class="grid gap-2">
                    <Label for="mission-subject">Subject</Label>
                    <Input id="mission-subject" name="subject" required />
                    <InputError :message="errors.subject" />
                </div>
                <div class="grid gap-2">
                    <Label for="mission-level">Level</Label>
                    <Input id="mission-level" name="level" required />
                    <InputError :message="errors.level" />
                </div>
                <div class="md:col-span-2">
                    <Button type="submit" :disabled="processing">
                        <Map />
                        Create draft
                    </Button>
                </div>
            </Form>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Your drafts"
                description="Private to your teacher account"
            />
            <div v-if="missions.length" class="divide-y border-y">
                <div
                    v-for="mission in missions"
                    :key="mission.id"
                    class="flex flex-col gap-4 py-5 sm:flex-row sm:items-center"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium">{{ mission.title }}</p>
                            <Badge variant="outline">Draft</Badge>
                            <Badge variant="secondary"
                                >{{ mission.nodes_count }} nodes</Badge
                            >
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ mission.subject }} · {{ mission.level }}
                        </p>
                    </div>
                    <Button as-child variant="outline">
                        <Link :href="`/teacher/missions/${mission.id}`">
                            Edit
                            <ArrowRight />
                        </Link>
                    </Button>
                </div>
            </div>
            <p v-else class="border-y py-6 text-sm text-muted-foreground">
                No mission drafts yet.
            </p>
        </section>

        <p class="border-y py-5 text-sm text-muted-foreground">
            Publishing, assignment, student access, progress, and AI generation
            are not available yet.
        </p>
    </main>
</template>
