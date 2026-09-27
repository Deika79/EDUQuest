<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    CheckCircle2,
    ExternalLink,
    RotateCcw,
    XCircle,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Flashcard = { id: number; front: string; back: string };
type QuizOption = { id: number; position: number; text: string };
type QuizQuestion = {
    id: number;
    position: number;
    statement: string;
    options: QuizOption[];
};
type QuizFeedbackAnswer = {
    question_id: number;
    selected_option_id: number;
    correct_option_id: number;
    correct: boolean;
    explanation: string;
};
type Quiz = {
    pass_threshold: number;
    questions: QuizQuestion[];
    attempt_count: number;
    best_score: number;
    ever_passed: boolean;
    latest_feedback: {
        score: number;
        correct_answers: number;
        total_questions: number;
        passed: boolean;
        answers: QuizFeedbackAnswer[];
    } | null;
};
type Node = {
    id: number;
    position: number;
    type: 'explanation' | 'video' | 'quiz' | 'flashcards';
    title: string;
    body: string | null;
    video: { embed_url: string; external_url: string } | null;
    flashcards: Flashcard[];
    quiz: Quiz | null;
    completed: boolean;
};

const props = defineProps<{ enrollmentId: number; node: Node }>();
const revealedCards = ref<number[]>([]);
const form = useForm({ confirmed: false, flashcard_ids: [] as number[] });
const quizForm = useForm({
    answers:
        props.node.quiz?.questions.map((question) => ({
            question_id: question.id,
            option_id: null as number | null,
        })) ?? [],
});

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

const allQuestionsAnswered = computed(() =>
    quizForm.answers.every((answer) => answer.option_id !== null),
);

const submitQuiz = () => {
    quizForm.post(
        `/student/missions/${props.enrollmentId}/nodes/${props.node.id}/quiz-attempts`,
        { preserveScroll: true },
    );
};

const feedbackFor = (questionId: number) =>
    props.node.quiz?.latest_feedback?.answers.find(
        (answer) => answer.question_id === questionId,
    );

const optionFeedbackClass = (questionId: number, optionId: number) => {
    const feedback = feedbackFor(questionId);

    if (feedback?.correct_option_id === optionId) {
        return 'border-green-600 bg-green-50 dark:bg-green-950/20';
    }

    if (feedback?.selected_option_id === optionId && !feedback.correct) {
        return 'border-destructive bg-destructive/5';
    }

    return '';
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

        <section v-else-if="node.type === 'flashcards'" class="space-y-5">
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

        <section v-else-if="node.quiz" class="space-y-6">
            <div class="border-y py-4 text-sm text-muted-foreground">
                <p>
                    Answer every question, then submit the whole attempt. Your
                    answers are saved only when you submit; unfinished drafts
                    are not retained.
                </p>
                <p class="mt-2">
                    Pass mark: {{ node.quiz.pass_threshold }}%. Attempts:
                    {{ node.quiz.attempt_count }}. Best score:
                    {{ node.quiz.best_score.toFixed(2) }}%.
                </p>
            </div>

            <div
                v-if="node.quiz.latest_feedback"
                class="space-y-4 border-y py-5"
            >
                <div class="flex flex-wrap items-center gap-3">
                    <CheckCircle2
                        v-if="node.quiz.latest_feedback.passed"
                        class="size-5 text-green-700"
                    />
                    <XCircle v-else class="size-5 text-destructive" />
                    <strong>
                        Latest attempt:
                        {{ node.quiz.latest_feedback.score.toFixed(2) }}%
                    </strong>
                    <Badge
                        :variant="
                            node.quiz.latest_feedback.passed
                                ? 'default'
                                : 'destructive'
                        "
                    >
                        {{
                            node.quiz.latest_feedback.passed
                                ? 'Passed'
                                : 'Not passed'
                        }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ node.quiz.latest_feedback.correct_answers }} of
                    {{ node.quiz.latest_feedback.total_questions }} correct.
                    Review the explanations below before trying again.
                </p>
            </div>

            <form class="space-y-8" @submit.prevent="submitQuiz">
                <fieldset
                    v-for="(question, questionIndex) in node.quiz.questions"
                    :key="question.id"
                    class="space-y-4 border-b pb-7"
                >
                    <legend class="font-medium">
                        {{ question.position }}. {{ question.statement }}
                    </legend>
                    <label
                        v-for="option in question.options"
                        :key="option.id"
                        class="flex items-start gap-3 border p-3 text-sm"
                        :class="optionFeedbackClass(question.id, option.id)"
                    >
                        <input
                            v-model="quizForm.answers[questionIndex].option_id"
                            type="radio"
                            :name="`question-${question.id}`"
                            :value="option.id"
                            class="mt-0.5 size-4"
                        />
                        <span>{{ option.text }}</span>
                    </label>
                    <div
                        v-if="feedbackFor(question.id)"
                        class="space-y-2 border-l-4 p-3 text-sm"
                        :class="
                            feedbackFor(question.id)?.correct
                                ? 'border-green-600'
                                : 'border-destructive'
                        "
                    >
                        <p class="font-medium">
                            {{
                                feedbackFor(question.id)?.correct
                                    ? 'Correct answer.'
                                    : 'Review this answer.'
                            }}
                        </p>
                        <p>{{ feedbackFor(question.id)?.explanation }}</p>
                        <p
                            v-if="!feedbackFor(question.id)?.correct"
                            class="text-muted-foreground"
                        >
                            Correct option:
                            {{
                                question.options.findIndex(
                                    (option) =>
                                        option.id ===
                                        feedbackFor(question.id)
                                            ?.correct_option_id,
                                ) + 1
                            }}.
                        </p>
                    </div>
                </fieldset>
                <InputError :message="quizForm.errors.answers" />
                <Button
                    type="submit"
                    :disabled="quizForm.processing || !allQuestionsAnswered"
                >
                    <Check /> Submit answers
                </Button>
            </form>
        </section>

        <form
            v-if="node.type !== 'quiz' && !node.completed"
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
            v-else-if="node.type !== 'quiz'"
            class="flex items-center gap-2 border-y py-5 text-sm text-green-700"
        >
            <Check class="size-5" /> This stage is already complete. Reviewing
            it again does not add points.
        </p>
    </main>
</template>
