<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, UserPlus } from '@lucide/vue';
import StudentEnrollmentController from '@/actions/App/Http/Controllers/Teacher/StudentEnrollmentController';
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
};

type Membership = {
    id: number;
    active: boolean;
    student: {
        id: number;
        name: string;
        username: string;
        email: string | null;
        account_active: boolean;
        managed_by_current_teacher: boolean;
    };
};

const props = defineProps<{
    classroom: Classroom;
    memberships: Membership[];
}>();

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
    <Head :title="classroom.name" />
    <main
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-10 p-4 md:p-8"
    >
        <section class="space-y-6">
            <Button as-child variant="ghost" class="w-fit">
                <Link href="/teacher/classes">
                    <ArrowLeft />
                    Classes
                </Link>
            </Button>
            <Heading
                :title="classroom.name"
                :description="`${classroom.level} · ${classroom.subject}`"
            />

            <Form
                v-bind="TeacherClassroomController.update.form(classroom.id)"
                v-slot="{ errors, processing }"
                class="grid gap-4 border-y py-6 md:grid-cols-3"
            >
                <div class="grid gap-2">
                    <Label for="class-name">Class name</Label>
                    <Input
                        id="class-name"
                        name="name"
                        :default-value="classroom.name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="class-level">Level</Label>
                    <Input
                        id="class-level"
                        name="level"
                        :default-value="classroom.level"
                        required
                    />
                    <InputError :message="errors.level" />
                </div>
                <div class="grid gap-2">
                    <Label for="class-subject">Subject</Label>
                    <Input
                        id="class-subject"
                        name="subject"
                        :default-value="classroom.subject"
                        required
                    />
                    <InputError :message="errors.subject" />
                </div>
                <div class="md:col-span-3">
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                    >
                        Save class
                    </Button>
                </div>
            </Form>
        </section>

        <section class="space-y-6">
            <Heading
                variant="small"
                title="Create student account"
                description="New account with a temporary password"
            />
            <Form
                v-bind="StudentEnrollmentController.store.form(classroom.id)"
                :reset-on-success="[
                    'name',
                    'username',
                    'email',
                    'password',
                    'password_confirmation',
                ]"
                v-slot="{ errors, processing }"
                class="grid gap-4 border-y py-6 md:grid-cols-2"
            >
                <div class="grid gap-2">
                    <Label for="student-name">Student name or alias</Label>
                    <Input id="student-name" name="name" required />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="student-username">Username</Label>
                    <Input
                        id="student-username"
                        name="username"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="errors.username" />
                </div>
                <div class="grid gap-2">
                    <Label for="student-email">Email address (optional)</Label>
                    <Input
                        id="student-email"
                        name="email"
                        type="email"
                        autocomplete="off"
                    />
                    <InputError :message="errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="student-password">Temporary password</Label>
                    <Input
                        id="student-password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                    />
                    <InputError :message="errors.password" />
                </div>
                <div class="grid gap-2 md:col-start-2">
                    <Label for="student-password-confirmation">
                        Confirm temporary password
                    </Label>
                    <Input
                        id="student-password-confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                    />
                </div>
                <div class="md:col-span-2">
                    <Button type="submit" :disabled="processing">
                        <UserPlus />
                        Create and enroll
                    </Button>
                </div>
            </Form>
        </section>

        <section class="space-y-6">
            <Heading
                variant="small"
                title="Enroll existing student"
                description="Use the exact username provided by the student"
            />
            <Form
                v-bind="
                    StudentEnrollmentController.storeExisting.form(classroom.id)
                "
                :reset-on-success="['username']"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-4 border-y py-6 sm:flex-row sm:items-end"
            >
                <div class="grid min-w-0 flex-1 gap-2">
                    <Label for="existing-username">Existing username</Label>
                    <Input
                        id="existing-username"
                        name="username"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="errors.existing_username" />
                </div>
                <Button type="submit" variant="outline" :disabled="processing">
                    Enroll
                </Button>
            </Form>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Students"
                description="Enrollment and account status are independent"
            />
            <div v-if="memberships.length" class="divide-y border-y">
                <div
                    v-for="membership in memberships"
                    :key="membership.id"
                    class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium">
                                {{ membership.student.name }}
                            </p>
                            <Badge
                                :variant="
                                    membership.active ? 'default' : 'secondary'
                                "
                            >
                                {{
                                    membership.active
                                        ? 'Enrolled'
                                        : 'Not enrolled'
                                }}
                            </Badge>
                            <Badge
                                v-if="!membership.student.account_active"
                                variant="destructive"
                            >
                                Account inactive
                            </Badge>
                            <Badge
                                v-if="
                                    !membership.student
                                        .managed_by_current_teacher
                                "
                                variant="outline"
                            >
                                Shared account
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ membership.student.username }}
                            <template v-if="membership.student.email">
                                · {{ membership.student.email }}
                            </template>
                        </p>
                    </div>
                    <Form
                        v-bind="
                            StudentEnrollmentController.update.form({
                                classroom: props.classroom.id,
                                membership: membership.id,
                            })
                        "
                    >
                        <input
                            type="hidden"
                            name="active"
                            :value="membership.active ? 0 : 1"
                        />
                        <Button
                            type="submit"
                            :variant="membership.active ? 'outline' : 'default'"
                        >
                            {{ membership.active ? 'Remove' : 'Reinstate' }}
                        </Button>
                    </Form>
                </div>
            </div>
            <p v-else class="border-y py-6 text-sm text-muted-foreground">
                No students enrolled yet.
            </p>
        </section>

        <p class="border-y py-5 text-sm text-muted-foreground">
            Missions, assignments, and student progress are not available yet.
        </p>
    </main>
</template>
