<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CircleAlert, LoaderCircle, Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type ProviderStatus = {
    configured: boolean;
    model: string;
    dailyLimit: number;
    remainingToday: number;
    generationInProgress: boolean;
};

const props = defineProps<{
    provider: ProviderStatus;
    requestToken: string;
}>();

const form = useForm({
    request_token: props.requestToken,
    topic: '',
    subject: '',
    level: '',
    objectives: '',
    difficulty: 'intermediate',
    node_count: 5,
    instructions: '',
});

const generationError = computed(
    () => (form.errors as Record<string, string>).generation,
);

const submit = () => {
    form.post('/teacher/missions/generate', {
        preserveScroll: true,
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Teacher', href: dashboard() },
            { title: 'Missions', href: '/teacher/missions' },
            { title: 'Generar con IA', href: '/teacher/missions/generate' },
        ],
    },
});
</script>

<template>
    <Head title="Generar borrador con IA" />
    <main
        class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-8 p-4 md:p-8"
    >
        <Button as-child variant="ghost" class="w-fit">
            <Link href="/teacher/missions"><ArrowLeft /> Misiones</Link>
        </Button>

        <section class="space-y-5">
            <Heading
                title="Generar borrador con IA"
                description="Describe el repaso y revisa despues cada actividad en el editor habitual"
            />

            <div
                class="flex items-start gap-3 border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-950"
            >
                <CircleAlert
                    class="mt-0.5 size-5 shrink-0"
                    aria-hidden="true"
                />
                <p>
                    El contenido generado puede contener errores. Revisa nivel,
                    explicaciones, respuestas y recursos antes de publicar. Los
                    videos quedan pendientes de seleccion y verificacion.
                </p>
            </div>

            <div class="border-y py-4 text-sm text-muted-foreground">
                <p v-if="provider.configured">
                    Proveedor configurado · modelo {{ provider.model }} ·
                    {{ provider.remainingToday }} de
                    {{ provider.dailyLimit }} generaciones disponibles hoy.
                </p>
                <p v-else class="font-medium text-destructive" role="status">
                    La generacion asistida no esta configurada en este entorno.
                    Puedes seguir creando misiones manualmente.
                </p>
                <p
                    v-if="provider.generationInProgress"
                    class="mt-2 font-medium text-amber-700"
                    role="status"
                >
                    Ya hay una generacion en curso para tu cuenta.
                </p>
            </div>
        </section>

        <form class="grid gap-5 md:grid-cols-2" @submit.prevent="submit">
            <div class="grid gap-2 md:col-span-2">
                <Label for="ai-topic">Tema</Label>
                <Input
                    id="ai-topic"
                    v-model="form.topic"
                    maxlength="200"
                    required
                />
                <InputError :message="form.errors.topic" />
            </div>

            <div class="grid gap-2">
                <Label for="ai-subject">Asignatura</Label>
                <Input
                    id="ai-subject"
                    v-model="form.subject"
                    maxlength="120"
                    required
                />
                <InputError :message="form.errors.subject" />
            </div>

            <div class="grid gap-2">
                <Label for="ai-level">Nivel educativo</Label>
                <Input
                    id="ai-level"
                    v-model="form.level"
                    maxlength="80"
                    required
                />
                <InputError :message="form.errors.level" />
            </div>

            <div class="grid gap-2 md:col-span-2">
                <Label for="ai-objectives">Objetivos de repaso</Label>
                <textarea
                    id="ai-objectives"
                    v-model="form.objectives"
                    rows="5"
                    maxlength="1500"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    required
                />
                <InputError :message="form.errors.objectives" />
            </div>

            <div class="grid gap-2">
                <Label for="ai-difficulty">Dificultad</Label>
                <select
                    id="ai-difficulty"
                    v-model="form.difficulty"
                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <option value="basic">Basica</option>
                    <option value="intermediate">Intermedia</option>
                    <option value="advanced">Avanzada</option>
                </select>
                <InputError :message="form.errors.difficulty" />
            </div>

            <div class="grid gap-2">
                <Label for="ai-node-count">Numero de nodos</Label>
                <Input
                    id="ai-node-count"
                    v-model="form.node_count"
                    type="number"
                    min="4"
                    max="8"
                    required
                />
                <InputError :message="form.errors.node_count" />
            </div>

            <div class="grid gap-2 md:col-span-2">
                <Label for="ai-instructions">Otras indicaciones</Label>
                <textarea
                    id="ai-instructions"
                    v-model="form.instructions"
                    rows="4"
                    maxlength="1500"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                />
                <InputError :message="form.errors.instructions" />
            </div>

            <InputError class="md:col-span-2" :message="generationError" />

            <div class="flex flex-wrap items-center gap-3 md:col-span-2">
                <Button
                    type="submit"
                    :disabled="
                        form.processing ||
                        !provider.configured ||
                        provider.remainingToday < 1 ||
                        provider.generationInProgress
                    "
                >
                    <LoaderCircle
                        v-if="form.processing"
                        class="animate-spin"
                        aria-hidden="true"
                    />
                    <Sparkles v-else aria-hidden="true" />
                    {{
                        form.processing
                            ? 'Generando borrador...'
                            : 'Generar borrador'
                    }}
                </Button>
                <Button as-child variant="outline">
                    <Link href="/teacher/missions">Crear manualmente</Link>
                </Button>
            </div>
        </form>

        <p class="border-y py-4 text-sm text-muted-foreground">
            Solo se envian al proveedor los campos de este formulario. No se
            envian alumnos, cuentas, intentos ni resultados. Una respuesta
            valida crea un borrador propio y nunca publica ni asigna la mision.
        </p>
    </main>
</template>
