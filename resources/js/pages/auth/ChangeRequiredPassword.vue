<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import RequiredPasswordController from '@/actions/App/Http/Controllers/Auth/RequiredPasswordController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Choose a new password',
        description: 'Replace the temporary password before continuing',
    },
});
</script>

<template>
    <Head title="Change password" />
    <Form
        v-bind="RequiredPasswordController.update.form()"
        :reset-on-success="[
            'current_password',
            'password',
            'password_confirmation',
        ]"
        v-slot="{ errors, processing }"
        class="grid gap-5"
    >
        <div class="grid gap-2">
            <Label for="current_password">Current password</Label>
            <PasswordInput
                id="current_password"
                name="current_password"
                autocomplete="current-password"
                required
            />
            <InputError :message="errors.current_password" />
        </div>
        <div class="grid gap-2">
            <Label for="password">New password</Label>
            <PasswordInput
                id="password"
                name="password"
                autocomplete="new-password"
                required
            />
            <InputError :message="errors.password" />
        </div>
        <div class="grid gap-2">
            <Label for="password_confirmation">Confirm new password</Label>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                autocomplete="new-password"
                required
            />
        </div>
        <Button type="submit" :disabled="processing">
            <Spinner v-if="processing" />
            Save password
        </Button>
    </Form>
</template>
