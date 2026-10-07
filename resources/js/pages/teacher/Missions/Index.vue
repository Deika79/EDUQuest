<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Map, Sparkles } from '@lucide/vue';
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
    map_theme: MapTheme;
    status: 'draft' | 'published' | 'archived';
    source: 'manual' | 'ai';
    nodes_count: number;
    assignments_count: number;
    updated_at: string;
};

defineProps<{ missions: MissionSummary[] }>();

type MapTheme = 'fantasy' | 'science' | 'old_west';

const mapThemes: Record<MapTheme, string> = {
    fantasy: 'Fantasía',
    science: 'Ciencia ficción',
    old_west: 'Oeste',
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Docente', href: dashboard() },
            { title: 'Misiones', href: '/teacher/missions' },
        ],
    },
});
</script>

<template>
    <Head title="Misiones" />
    <main
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-10 p-4 md:p-8"
    >
        <section class="space-y-6">
            <Heading
                title="Misiones"
                description="Crea, publica y distribuye tus propios contenidos"
            />
            <div class="flex flex-wrap gap-3 border-y py-5">
                <Button as-child>
                    <Link href="/teacher/missions/generate">
                        <Sparkles /> Generar borrador con IA
                    </Link>
                </Button>
                <p class="self-center text-sm text-muted-foreground">
                    La creación manual sigue disponible. La IA nunca publica ni
                    asigna contenido.
                </p>
            </div>
            <Form
                v-bind="TeacherMissionController.store.form()"
                :reset-on-success="['title', 'description', 'subject', 'level']"
                v-slot="{ errors, processing }"
                class="grid gap-4 border-y py-6 md:grid-cols-2"
            >
                <div class="grid gap-2 md:col-span-2">
                    <Label for="mission-title">Título</Label>
                    <Input id="mission-title" name="title" required />
                    <InputError :message="errors.title" />
                </div>
                <div class="grid gap-2 md:col-span-2">
                    <Label for="mission-description"
                        >Descripción y contexto narrativo</Label
                    >
                    <textarea
                        id="mission-description"
                        name="description"
                        rows="4"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        required
                    />
                    <InputError :message="errors.description" />
                </div>
                <div class="grid gap-2">
                    <Label for="mission-subject">Asignatura</Label>
                    <Input id="mission-subject" name="subject" required />
                    <InputError :message="errors.subject" />
                </div>
                <div class="grid gap-2">
                    <Label for="mission-level">Nivel</Label>
                    <Input id="mission-level" name="level" required />
                    <InputError :message="errors.level" />
                </div>
                <div class="grid gap-2 md:col-span-2">
                    <Label for="mission-map-theme">Escenario del mapa</Label>
                    <select
                        id="mission-map-theme"
                        name="map_theme"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option value="fantasy">Fantasía</option>
                        <option value="science">Ciencia ficción</option>
                        <option value="old_west">Oeste</option>
                    </select>
                    <InputError :message="errors.map_theme" />
                </div>
                <div class="md:col-span-2">
                    <Button type="submit" :disabled="processing">
                        <Map />
                        Crear borrador
                    </Button>
                </div>
            </Form>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Tus misiones"
                description="Borradores, publicaciones y contenido archivado"
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
                            <Badge
                                :variant="
                                    mission.status === 'published'
                                        ? 'default'
                                        : mission.status === 'archived'
                                          ? 'secondary'
                                          : 'outline'
                                "
                            >
                                {{
                                    mission.status === 'draft'
                                        ? 'borrador'
                                        : mission.status === 'published'
                                          ? 'publicada'
                                          : 'archivada'
                                }}
                            </Badge>
                            <Badge variant="secondary"
                                >{{ mission.nodes_count }} etapas</Badge
                            >
                            <Badge variant="outline">
                                {{ mapThemes[mission.map_theme] }}
                            </Badge>
                            <Badge
                                v-if="mission.source === 'ai'"
                                variant="outline"
                            >
                                Borrador IA
                            </Badge>
                            <Badge variant="outline">
                                {{ mission.assignments_count }} asignaciones
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ mission.subject }} · {{ mission.level }}
                        </p>
                    </div>
                    <Button as-child variant="outline">
                        <Link :href="`/teacher/missions/${mission.id}`">
                            {{
                                mission.status === 'draft' ? 'Editar' : 'Abrir'
                            }}
                            <ArrowRight />
                        </Link>
                    </Button>
                </div>
            </div>
            <p v-else class="border-y py-6 text-sm text-muted-foreground">
                Todavía no hay misiones.
            </p>
        </section>

        <p class="border-y py-5 text-sm text-muted-foreground">
            El progreso del alumnado está disponible en Seguimiento. Los
            borradores asistidos por IA siempre deben revisarse antes de
            publicarse.
        </p>
    </main>
</template>
