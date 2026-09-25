<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Teacher', href: dashboard() }] },
});
</script>

<template>
    <Head title="Teacher" />
    <main class="mx-auto w-full max-w-5xl p-4 md:p-8">
        <Heading
            title="Teacher profile"
            description="Your workspace is ready."
        />
        <p class="mt-6 border-y py-6 text-sm text-muted-foreground">
            Class and mission tools are not available in this milestone yet.
        </p>
    </main>
</template>
