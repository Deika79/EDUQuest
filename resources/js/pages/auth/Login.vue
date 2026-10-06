<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import PasskeyVerify from '@/components/PasskeyVerify.vue';

defineOptions({
    layout: {
        title: 'Accede a tu cuenta',
        description: 'Introduce tu usuario y contraseña para continuar',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Acceso" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <PasskeyVerify
        label="Acceder con una llave de acceso"
        loading-label="Comprobando llave..."
        separator="O continúa con tu usuario"
    />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="username">Usuario</Label>
                <Input
                    id="username"
                    type="text"
                    name="username"
                    required
                    v-focus
                    autocomplete="username"
                    placeholder="Tu usuario"
                />
                <InputError :message="errors.username" />
            </div>

            <div class="grid gap-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <Label for="password">Contraseña</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="min-w-0 text-right text-sm whitespace-normal"
                    >
                        ¿Has olvidado tu contraseña?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Tu contraseña"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="0" />
                    <span>Recordarme</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Acceder
            </Button>
        </div>
    </Form>
</template>
