<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BarChart3 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Docente', href: dashboard() }] },
});
</script>

<template>
    <Head title="Docente" />
    <main class="mx-auto w-full max-w-5xl p-4 md:p-8">
        <Heading
            title="Perfil docente"
            description="Tu espacio de trabajo está preparado."
        />
        <div
            class="mt-6 flex flex-col gap-4 border-y py-6 sm:flex-row sm:items-center"
        >
            <div class="min-w-0 flex-1">
                <p class="font-medium">Clases y alumnado</p>
                <p class="text-sm text-muted-foreground">
                    Gestiona tus grupos docentes y sus matrículas activas.
                </p>
            </div>
            <Button as-child>
                <Link href="/teacher/classes">
                    Abrir clases
                    <ArrowRight />
                </Link>
            </Button>
        </div>
        <div
            class="mt-6 flex flex-col gap-4 border-y py-6 sm:flex-row sm:items-center"
        >
            <div class="min-w-0 flex-1">
                <p class="font-medium">Misiones</p>
                <p class="text-sm text-muted-foreground">
                    Crea, revisa y valida el contenido de las misiones.
                </p>
            </div>
            <Button as-child>
                <Link href="/teacher/missions">
                    Abrir misiones
                    <ArrowRight />
                </Link>
            </Button>
        </div>
        <div
            class="mt-6 flex flex-col gap-4 border-y py-6 sm:flex-row sm:items-center"
        >
            <div class="min-w-0 flex-1">
                <p class="font-medium">Seguimiento del alumnado</p>
                <p class="text-sm text-muted-foreground">
                    Revisa progreso, puntos, intentos y mejores notas.
                </p>
            </div>
            <Button as-child>
                <Link href="/teacher/tracking">
                    <BarChart3 /> Abrir seguimiento <ArrowRight />
                </Link>
            </Button>
        </div>
        <p class="mt-6 text-sm text-muted-foreground">
            La generación asistida crea borradores revisables y nunca publica
            automáticamente.
        </p>
    </main>
</template>
