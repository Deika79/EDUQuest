<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, ExternalLink, RotateCcw } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Flashcard = { id: number; front: string; back: string };
type Node = {
    id: number;
    position: number;
    type: 'explanation' | 'video' | 'flashcards';
    title: string;
    body: string | null;
    video: { embed_url: string; external_url: string } | null;
    flashcards: Flashcard[];
    completed: boolean;
};

const props = defineProps<{ enrollmentId: number; node: Node }>();
const revealedCards = ref<number[]>([]);
const form = useForm({ confirmed: false, flashcard_ids: [] as number[] });

const allCardsReviewed = computed(
    () =>
        props.node.type !== 'flashcards' ||
        form.flashcard_ids.length === props.node.flashcards.length,
);

const revealCard = (cardId: number) => {
    if (!revealedCards.value.includes(cardId)) {
        revealedCards.value.push(cardId);
        form.flashcard_ids.push(cardId);
    }
};

const submit = () => {
    form.post(
        `/student/missions/${props.enrollmentId}/nodes/${props.node.id}/complete`,
    );
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My missions', href: '/student/missions' }],
    },
});
</script>

<template>
    <Head :title="node.title" />
    <main
        class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-8 p-4 md:p-8"
    >
        <Button as-child variant="ghost" class="w-fit">
            <Link :href="`/student/missions/${enrollmentId}`"
                ><ArrowLeft /> Mission map</Link
            >
        </Button>

        <section class="space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <Badge variant="outline">Stage {{ node.position }}</Badge>
                <Badge variant="secondary">{{ node.type }}</Badge>
                <Badge v-if="node.completed">Completed</Badge>
            </div>
            <Heading
                :title="node.title"
                description="Complete this stage when you have reviewed its content"
            />
        </section>

        <section v-if="node.type === 'explanation'" class="border-y py-8">
            <p class="leading-7 whitespace-pre-wrap">{{ node.body }}</p>
        </section>

        <section
            v-else-if="node.type === 'video' && node.video"
            class="space-y-4 border-y py-6"
        >
            <iframe
                :src="node.video.embed_url"
                :title="node.title"
                class="aspect-video w-full border"
                allow="
                    accelerometer;
                    autoplay;
                    clipboard-write;
                    encrypted-media;
                    gyroscope;
                    picture-in-picture;
                "
                allowfullscreen
            />
            <Button as-child variant="outline">
                <a
                    :href="node.video.external_url"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <ExternalLink /> Open video in a new tab
                </a>
            </Button>
        </section>

        <section v-else class="space-y-5">
            <p class="text-sm text-muted-foreground">
                Reveal the back of every card before confirming your review.
            </p>
            <div class="grid gap-4 sm:grid-cols-2">
                <button
                    v-for="card in node.flashcards"
                    :key="card.id"
                    type="button"
                    class="flex min-h-40 flex-col items-center justify-center gap-3 border p-5 text-center focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    @click="revealCard(card.id)"
                >
                    <span class="text-xs font-medium text-muted-foreground">
                        {{ revealedCards.includes(card.id) ? 'Back' : 'Front' }}
                    </span>
                    <span class="font-medium">
                        {{
                            revealedCards.includes(card.id)
                                ? card.back
                                : card.front
                        }}
                    </span>
                    <span
                        class="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <Check
                            v-if="revealedCards.includes(card.id)"
                            class="size-4"
                        />
                        <RotateCcw v-else class="size-4" />
                        {{
                            revealedCards.includes(card.id)
                                ? 'Reviewed'
                                : 'Reveal'
                        }}
                    </span>
                </button>
            </div>
            <InputError :message="form.errors.flashcard_ids" />
        </section>

        <form
            v-if="!node.completed"
            class="space-y-4 border-y py-6"
            @submit.prevent="submit"
        >
            <label class="flex items-start gap-3 text-sm">
                <input
                    v-model="form.confirmed"
                    type="checkbox"
                    class="mt-1 size-4"
                />
                <span>
                    I confirm that I reviewed this resource. This records my
                    action but does not prove comprehension or, for video,
                    complete viewing.
                </span>
            </label>
            <InputError :message="form.errors.confirmed" />
            <Button
                type="submit"
                :disabled="
                    form.processing || !form.confirmed || !allCardsReviewed
                "
            >
                <Check /> Complete stage
            </Button>
        </form>
        <p
            v-else
            class="flex items-center gap-2 border-y py-5 text-sm text-green-700"
        >
            <Check class="size-5" /> This stage is already complete. Reviewing
            it again does not add points.
        </p>
    </main>
</template>
