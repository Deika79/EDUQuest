<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowRight, School } from '@lucide/vue';
import TeacherClassroomController from '@/actions/App/Http/Controllers/Teacher/TeacherClassroomController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type Classroom = {
    id: number;
    name: string;
    level: string;
    subject: string;
    active_students_count: number;
};

defineProps<{ classrooms: Classroom[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Teacher', href: dashboard() },
            { title: 'Classes', href: '/teacher/classes' },
        ],
    },
});
</script>

<template>
    <Head title="Classes" />
    <main
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-10 p-4 md:p-8"
    >
        <section class="space-y-6">
            <Heading title="Classes" description="Your teaching groups" />

            <Form
                v-bind="TeacherClassroomController.store.form()"
                :reset-on-success="['name', 'level', 'subject']"
                v-slot="{ errors, processing }"
                class="grid gap-4 border-y py-6 md:grid-cols-3"
            >
                <div class="grid gap-2">
                    <Label for="name">Class name</Label>
                    <Input id="name" name="name" required />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="level">Level</Label>
                    <Input id="level" name="level" required />
                    <InputError :message="errors.level" />
                </div>
                <div class="grid gap-2">
                    <Label for="subject">Subject</Label>
                    <Input id="subject" name="subject" required />
                    <InputError :message="errors.subject" />
                </div>
                <div class="md:col-span-3">
                    <Button type="submit" :disabled="processing">
                        <School />
                        Create class
                    </Button>
                </div>
            </Form>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Your classes"
                description="Only groups owned by your account"
            />
            <div v-if="classrooms.length" class="divide-y border-y">
                <div
                    v-for="classroom in classrooms"
                    :key="classroom.id"
                    class="flex flex-col gap-4 py-5 sm:flex-row sm:items-center"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium">{{ classroom.name }}</p>
                            <Badge variant="secondary">
                                {{ classroom.active_students_count }} active
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ classroom.level }} · {{ classroom.subject }}
                        </p>
                    </div>
                    <Button as-child variant="outline">
                        <Link :href="`/teacher/classes/${classroom.id}`">
                            Manage
                            <ArrowRight />
                        </Link>
                    </Button>
                </div>
            </div>
            <p v-else class="border-y py-6 text-sm text-muted-foreground">
                No classes created yet.
            </p>
        </section>

        <p class="border-y py-5 text-sm text-muted-foreground">
            Mission assignments and progress are not available yet.
        </p>
    </main>
</template>
