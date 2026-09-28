<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 overflow-x-hidden bg-[#f7f9fc] p-6 text-[#101d36] md:p-10 dark:bg-[#071223] dark:text-white"
    >
        <div class="w-full max-w-sm min-w-0">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="rounded-md font-medium focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#a3610d] dark:focus-visible:outline-[#f5b845]"
                    >
                        <img
                            src="/brand/logo-horizontal-claro.svg"
                            alt="EDUQuest"
                            class="h-auto w-56 dark:hidden"
                        />
                        <img
                            src="/brand/logo-horizontal-oscuro.svg"
                            alt="EDUQuest"
                            class="hidden h-auto w-56 dark:block"
                        />
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
