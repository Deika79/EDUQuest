<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Student', href: dashboard() }] },
});
</script>

<template>
    <Head title="Student" />
    <main class="mx-auto w-full max-w-5xl p-4 md:p-8">
        <Heading title="Student profile" description="Your account is ready." />
        <p class="mt-6 border-y py-6 text-sm text-muted-foreground">
            Assigned learning activities will appear in a later milestone.
        </p>
    </main>
</template>
