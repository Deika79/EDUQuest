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
            { title: 'Docente', href: dashboard() },
            { title: 'Clases', href: '/teacher/classes' },
        ],
    },
});
</script>

<template>
    <Head title="Clases" />
    <main
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-10 p-4 md:p-8"
    >
        <section class="space-y-6">
            <Heading title="Clases" description="Tus grupos docentes" />

            <Form
                v-bind="TeacherClassroomController.store.form()"
                :reset-on-success="['name', 'level', 'subject']"
                v-slot="{ errors, processing }"
                class="grid gap-4 border-y py-6 md:grid-cols-3"
            >
                <div class="grid gap-2">
                    <Label for="name">Nombre de la clase</Label>
                    <Input id="name" name="name" required />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="level">Nivel</Label>
                    <Input id="level" name="level" required />
                    <InputError :message="errors.level" />
                </div>
                <div class="grid gap-2">
                    <Label for="subject">Asignatura</Label>
                    <Input id="subject" name="subject" required />
                    <InputError :message="errors.subject" />
                </div>
                <div class="md:col-span-3">
                    <Button type="submit" :disabled="processing">
                        <School />
                        Crear clase
                    </Button>
                </div>
            </Form>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Tus clases"
                description="Solo grupos propiedad de tu cuenta"
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
                                {{ classroom.active_students_count }} activos
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ classroom.level }} · {{ classroom.subject }}
                        </p>
                    </div>
                    <Button as-child variant="outline">
                        <Link :href="`/teacher/classes/${classroom.id}`">
                            Gestionar
                            <ArrowRight />
                        </Link>
                    </Button>
                </div>
            </div>
            <p v-else class="border-y py-6 text-sm text-muted-foreground">
                Todavía no hay clases creadas.
            </p>
        </section>

        <p class="border-y py-5 text-sm text-muted-foreground">
            Las asignaciones se gestionan desde cada misión publicada. Abre
            Seguimiento para comparar el progreso guardado del alumnado.
        </p>
    </main>
</template>
