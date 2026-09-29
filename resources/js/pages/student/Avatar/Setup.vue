<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Check, Compass } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type Character = {
    key: string;
    label: string;
    image: string;
};

const props = defineProps<{
    characters: Character[];
    selection: { character_key: string };
}>();

const form = useForm({ ...props.selection });

function submit(): void {
    form.post('/student/avatar/setup', { preserveScroll: true });
}

defineOptions({
    layout: { breadcrumbs: [{ title: 'Mi avatar', href: dashboard() }] },
});
</script>

<template>
    <Head title="Elige tu personaje" />
    <main class="mx-auto w-full max-w-5xl flex-1 p-4 md:p-8">
        <header class="mx-auto max-w-2xl space-y-3 text-center">
            <p class="text-sm font-semibold text-[#157f84]">Primer acceso</p>
            <h1
                v-focus
                tabindex="-1"
                class="text-2xl font-semibold outline-none sm:text-3xl"
            >
                Elige tu personaje
            </h1>
            <p class="text-sm text-muted-foreground sm:text-base">
                Elige quién te acompañará en tus misiones. La elección no
                representa género ni cambia tus actividades, puntos o
                resultados.
            </p>
        </header>

        <form class="mt-8" @submit.prevent="submit">
            <fieldset>
                <legend class="sr-only">Personaje inicial</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div
                        v-for="character in characters"
                        :key="character.key"
                        class="min-w-0"
                    >
                        <input
                            :id="`character-${character.key}`"
                            v-model="form.character_key"
                            type="radio"
                            name="character_key"
                            :value="character.key"
                            class="peer sr-only"
                        />
                        <label
                            :for="`character-${character.key}`"
                            class="group relative flex min-h-[28rem] cursor-pointer flex-col overflow-hidden border-2 bg-[#edf5f8] p-4 transition-colors peer-checked:border-[#157f84] peer-checked:bg-[#e3f3f1] peer-focus-visible:outline-2 peer-focus-visible:outline-offset-4 peer-focus-visible:outline-[#a3610d] dark:bg-[#12243a] dark:peer-checked:bg-[#123c43]"
                        >
                            <span
                                v-if="form.character_key === character.key"
                                class="absolute top-4 right-4 z-10 flex size-8 items-center justify-center rounded-full border-2 border-current bg-background text-[#157f84]"
                                aria-hidden="true"
                            >
                                <Check class="size-5" />
                            </span>
                            <img
                                :src="character.image"
                                :alt="`Ilustración de ${character.label}`"
                                width="512"
                                height="768"
                                class="mx-auto h-[22rem] w-full object-contain object-bottom sm:h-[26rem]"
                            />
                            <span
                                class="mt-auto border-t border-[#17324d]/15 pt-3 text-center text-lg font-semibold"
                            >
                                {{ character.label }}
                            </span>
                            <span
                                class="mt-1 text-center text-sm text-muted-foreground"
                            >
                                Apariencia inicial incluida
                            </span>
                        </label>
                    </div>
                </div>
                <InputError class="mt-3" :message="form.errors.character_key" />
            </fieldset>

            <div class="mt-7 flex flex-col items-center gap-3 text-center">
                <p class="max-w-2xl text-sm text-muted-foreground">
                    Esta es la selección funcional inicial. Otras apariencias y
                    la tienda se incorporarán en tareas posteriores de R09.
                </p>
                <Button type="submit" size="lg" :disabled="form.processing">
                    <Compass aria-hidden="true" />
                    {{
                        form.processing
                            ? 'Guardando...'
                            : 'Continuar a mis misiones'
                    }}
                </Button>
            </div>
        </form>
    </main>
</template>
