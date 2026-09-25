<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AdminTeacherController from '@/actions/App/Http/Controllers/Admin/AdminTeacherController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type Teacher = {
    id: number;
    name: string;
    username: string;
    email: string;
    active: boolean;
    must_change_password: boolean;
};

defineProps<{ teachers: Teacher[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Administration', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Administration" />
    <div
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-10 p-4 md:p-8"
    >
        <section class="space-y-6">
            <Heading
                title="Administrator profile"
                description="Teacher account management"
            />
            <Form
                v-bind="AdminTeacherController.store.form()"
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
                    <Label for="name">Full name</Label>
                    <Input id="name" name="name" required autocomplete="name" />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="username">Username</Label>
                    <Input
                        id="username"
                        name="username"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="errors.username" />
                </div>
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        required
                        autocomplete="email"
                    />
                    <InputError :message="errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="password">Temporary password</Label>
                    <Input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                    />
                    <InputError :message="errors.password" />
                </div>
                <div class="grid gap-2 md:col-start-2">
                    <Label for="password_confirmation"
                        >Confirm temporary password</Label
                    >
                    <Input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                    />
                </div>
                <div class="md:col-span-2">
                    <Button type="submit" :disabled="processing"
                        >Create teacher</Button
                    >
                </div>
            </Form>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Teachers"
                description="Active and inactive accounts"
            />
            <div v-if="teachers.length" class="divide-y border-y">
                <div
                    v-for="teacher in teachers"
                    :key="teacher.id"
                    class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium">{{ teacher.name }}</p>
                            <Badge
                                :variant="
                                    teacher.active ? 'default' : 'secondary'
                                "
                                >{{
                                    teacher.active ? 'Active' : 'Inactive'
                                }}</Badge
                            >
                            <Badge
                                v-if="teacher.must_change_password"
                                variant="outline"
                                >Password change pending</Badge
                            >
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ teacher.username }} · {{ teacher.email }}
                        </p>
                    </div>
                    <Form
                        v-bind="AdminTeacherController.update.form(teacher.id)"
                    >
                        <input
                            type="hidden"
                            name="active"
                            :value="teacher.active ? 0 : 1"
                        />
                        <Button
                            type="submit"
                            :variant="teacher.active ? 'outline' : 'default'"
                        >
                            {{ teacher.active ? 'Deactivate' : 'Activate' }}
                        </Button>
                    </Form>
                </div>
            </div>
            <p v-else class="border-y py-6 text-sm text-muted-foreground">
                No teacher accounts yet.
            </p>
        </section>
    </div>
</template>
