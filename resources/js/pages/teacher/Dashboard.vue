<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
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
        <div
            class="mt-6 flex flex-col gap-4 border-y py-6 sm:flex-row sm:items-center"
        >
            <div class="min-w-0 flex-1">
                <p class="font-medium">Classes and students</p>
                <p class="text-sm text-muted-foreground">
                    Manage your own teaching groups and active enrollments.
                </p>
            </div>
            <Button as-child>
                <Link href="/teacher/classes">
                    Open classes
                    <ArrowRight />
                </Link>
            </Button>
        </div>
        <p class="mt-6 border-y py-6 text-sm text-muted-foreground">
            Mission and progress tools are not available yet.
        </p>
    </main>
</template>
