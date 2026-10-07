<script setup lang="ts">
import { Form, useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    CircleAlert,
    Plus,
    Save,
    Trash2,
    X,
} from '@lucide/vue';
import MissionNodeController from '@/actions/App/Http/Controllers/Teacher/MissionNodeController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type NodeType = 'explanation' | 'video' | 'quiz' | 'flashcards';
type VideoProvider = 'youtube' | 'vimeo';

type QuizOption = {
    id?: number;
    text: string;
    is_correct: boolean;
};

type QuizQuestion = {
    id?: number;
    statement: string;
    explanation: string;
    options: QuizOption[];
};

type Flashcard = {
    id?: number;
    front: string;
    back: string;
};

export type MissionNode = {
    id: number;
    position: number;
    type: NodeType;
    title: string;
    body: string | null;
    video_provider: VideoProvider | null;
    video_reference: string | null;
    pass_threshold: number | null;
    coin_reward: number;
    review_required: boolean;
    review_note: string | null;
    questions: QuizQuestion[];
    flashcards: Flashcard[];
};

const props = defineProps<{
    missionId: number;
    node?: MissionNode;
    first?: boolean;
    last?: boolean;
}>();

const typeLabels: Record<NodeType, string> = {
    explanation: 'Explicación',
    video: 'Vídeo',
    quiz: 'Cuestionario',
    flashcards: 'Flashcards',
};

const blankOptions = (): QuizOption[] => [
    { text: '', is_correct: true },
    { text: '', is_correct: false },
];

const form = useForm({
    title: props.node?.title ?? '',
    type: props.node?.type ?? ('explanation' as NodeType),
    body: props.node?.body ?? '',
    video_provider: props.node?.video_provider ?? ('youtube' as VideoProvider),
    video_reference: props.node?.video_reference ?? '',
    pass_threshold: props.node?.pass_threshold ?? 70,
    coin_reward: props.node?.coin_reward ?? 0,
    questions: props.node?.questions.map((question) => ({
        statement: question.statement,
        explanation: question.explanation,
        options: question.options.map((option) => ({
            text: option.text,
            is_correct: option.is_correct,
        })),
    })) ?? [
        {
            statement: '',
            explanation: '',
            options: blankOptions(),
        },
    ],
    flashcards: props.node?.flashcards.map((card) => ({
        front: card.front,
        back: card.back,
    })) ?? [{ front: '', back: '' }],
});

const fieldError = (key: string): string | undefined => {
    const value = (form.errors as Record<string, string | string[]>)[key];

    return Array.isArray(value) ? value[0] : value;
};

const submit = () => {
    if (props.node) {
        form.patch(
            MissionNodeController.update.url({
                mission: props.missionId,
                node: props.node.id,
            }),
            { preserveScroll: true },
        );

        return;
    }

    form.post(MissionNodeController.store.url(props.missionId), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const addQuestion = () => {
    if (form.questions.length < 10) {
        form.questions.push({
            statement: '',
            explanation: '',
            options: blankOptions(),
        });
    }
};

const addOption = (questionIndex: number) => {
    const question = form.questions[questionIndex];

    if (question && question.options.length < 4) {
        question.options.push({ text: '', is_correct: false });
    }
};

const removeOption = (questionIndex: number, optionIndex: number) => {
    const question = form.questions[questionIndex];

    if (question && question.options.length > 2) {
        question.options.splice(optionIndex, 1);
        if (!question.options.some((option) => option.is_correct)) {
            question.options[0]!.is_correct = true;
        }
    }
};

const setCorrectOption = (questionIndex: number, optionIndex: number) => {
    form.questions[questionIndex]?.options.forEach((option, index) => {
        option.is_correct = index === optionIndex;
    });
};

const addFlashcard = () => {
    if (form.flashcards.length < 10) {
        form.flashcards.push({ front: '', back: '' });
    }
};
</script>

<template>
    <section class="space-y-5 border-y py-6">
        <div class="flex flex-wrap items-center gap-2">
            <Badge v-if="node" variant="secondary"
                >Etapa {{ node.position }}</Badge
            >
            <Badge variant="outline">{{ typeLabels[form.type] }}</Badge>
            <div v-if="node" class="ml-auto flex items-center gap-1">
                <Form
                    v-bind="
                        MissionNodeController.move.form({
                            mission: missionId,
                            node: node.id,
                        })
                    "
                >
                    <input type="hidden" name="direction" value="up" />
                    <Button
                        type="submit"
                        size="icon"
                        variant="ghost"
                        :disabled="first"
                        title="Subir etapa"
                    >
                        <ArrowUp />
                    </Button>
                </Form>
                <Form
                    v-bind="
                        MissionNodeController.move.form({
                            mission: missionId,
                            node: node.id,
                        })
                    "
                >
                    <input type="hidden" name="direction" value="down" />
                    <Button
                        type="submit"
                        size="icon"
                        variant="ghost"
                        :disabled="last"
                        title="Bajar etapa"
                    >
                        <ArrowDown />
                    </Button>
                </Form>
                <Form
                    v-bind="
                        MissionNodeController.destroy.form({
                            mission: missionId,
                            node: node.id,
                        })
                    "
                >
                    <Button
                        type="submit"
                        size="icon"
                        variant="ghost"
                        title="Eliminar etapa"
                    >
                        <Trash2 />
                    </Button>
                </Form>
            </div>
        </div>

        <div
            v-if="node?.review_required"
            class="flex items-start gap-3 border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-950"
            role="status"
        >
            <CircleAlert class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
            <p>
                {{ node.review_note }} Al guardar este nodo con un recurso
                valido, quedara marcado como revisado.
            </p>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label :for="`node-title-${node?.id ?? 'new'}`"
                        >Título</Label
                    >
                    <Input
                        :id="`node-title-${node?.id ?? 'new'}`"
                        v-model="form.title"
                        required
                    />
                    <InputError :message="fieldError('title')" />
                </div>
                <div class="grid gap-2">
                    <Label :for="`node-type-${node?.id ?? 'new'}`">Tipo</Label>
                    <select
                        :id="`node-type-${node?.id ?? 'new'}`"
                        v-model="form.type"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                    >
                        <option value="explanation">Explicación</option>
                        <option value="video">Vídeo</option>
                        <option value="quiz">Cuestionario</option>
                        <option value="flashcards">Flashcards</option>
                    </select>
                    <InputError :message="fieldError('type')" />
                </div>
            </div>

            <div class="grid max-w-xs gap-2">
                <Label :for="`coin-reward-${node?.id ?? 'new'}`"
                    >Recompensa en monedas</Label
                >
                <Input
                    :id="`coin-reward-${node?.id ?? 'new'}`"
                    v-model="form.coin_reward"
                    type="number"
                    min="0"
                    max="3"
                    required
                />
                <p class="text-sm text-muted-foreground">
                    Entre 0 y 3 por actividad; máximo 20 en toda la misión.
                </p>
                <InputError :message="fieldError('coin_reward')" />
            </div>

            <div v-if="form.type === 'explanation'" class="grid gap-2">
                <Label :for="`node-body-${node?.id ?? 'new'}`"
                    >Texto de explicación</Label
                >
                <textarea
                    :id="`node-body-${node?.id ?? 'new'}`"
                    v-model="form.body"
                    rows="7"
                    class="min-h-36 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                    required
                />
                <InputError :message="fieldError('body')" />
            </div>

            <div v-if="form.type === 'video'" class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label :for="`video-provider-${node?.id ?? 'new'}`"
                        >Proveedor</Label
                    >
                    <select
                        :id="`video-provider-${node?.id ?? 'new'}`"
                        v-model="form.video_provider"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                    >
                        <option value="youtube">YouTube</option>
                        <option value="vimeo">Vimeo</option>
                    </select>
                    <InputError :message="fieldError('video_provider')" />
                </div>
                <div class="grid gap-2">
                    <Label :for="`video-reference-${node?.id ?? 'new'}`"
                        >URL o ID del vídeo</Label
                    >
                    <Input
                        :id="`video-reference-${node?.id ?? 'new'}`"
                        v-model="form.video_reference"
                        required
                    />
                    <InputError :message="fieldError('video_reference')" />
                </div>
            </div>

            <div v-if="form.type === 'quiz'" class="space-y-6">
                <div class="grid max-w-xs gap-2">
                    <Label :for="`threshold-${node?.id ?? 'new'}`"
                        >Umbral de aprobado (%)</Label
                    >
                    <Input
                        :id="`threshold-${node?.id ?? 'new'}`"
                        v-model="form.pass_threshold"
                        type="number"
                        min="1"
                        max="100"
                        required
                    />
                    <InputError :message="fieldError('pass_threshold')" />
                </div>

                <div
                    v-for="(question, questionIndex) in form.questions"
                    :key="questionIndex"
                    class="space-y-4 border-l-2 pl-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-medium">
                            Pregunta {{ questionIndex + 1 }}
                        </p>
                        <Button
                            v-if="form.questions.length > 1"
                            type="button"
                            size="icon"
                            variant="ghost"
                            title="Eliminar pregunta"
                            @click="form.questions.splice(questionIndex, 1)"
                        >
                            <X />
                        </Button>
                    </div>
                    <div class="grid gap-2">
                        <Label
                            :for="`question-${node?.id ?? 'new'}-${questionIndex}`"
                            >Enunciado</Label
                        >
                        <Input
                            :id="`question-${node?.id ?? 'new'}-${questionIndex}`"
                            v-model="question.statement"
                            required
                        />
                        <InputError
                            :message="
                                fieldError(
                                    `questions.${questionIndex}.statement`,
                                )
                            "
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label
                            :for="`explanation-${node?.id ?? 'new'}-${questionIndex}`"
                            >Explicación del feedback</Label
                        >
                        <textarea
                            :id="`explanation-${node?.id ?? 'new'}-${questionIndex}`"
                            v-model="question.explanation"
                            rows="3"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                            required
                        />
                        <InputError
                            :message="
                                fieldError(
                                    `questions.${questionIndex}.explanation`,
                                )
                            "
                        />
                    </div>
                    <div class="space-y-3">
                        <div
                            v-for="(option, optionIndex) in question.options"
                            :key="optionIndex"
                            class="flex items-start gap-3"
                        >
                            <input
                                :id="`correct-${node?.id ?? 'new'}-${questionIndex}-${optionIndex}`"
                                type="radio"
                                :name="`correct-${node?.id ?? 'new'}-${questionIndex}`"
                                :checked="option.is_correct"
                                class="mt-3 size-4"
                                :aria-label="`Marcar la opción ${optionIndex + 1} como correcta`"
                                @change="
                                    setCorrectOption(questionIndex, optionIndex)
                                "
                            />
                            <div class="min-w-0 flex-1">
                                <Input
                                    v-model="option.text"
                                    :aria-label="`Opción ${optionIndex + 1}`"
                                    required
                                />
                                <InputError
                                    :message="
                                        fieldError(
                                            `questions.${questionIndex}.options.${optionIndex}.text`,
                                        )
                                    "
                                />
                            </div>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                :disabled="question.options.length <= 2"
                                title="Eliminar opción"
                                @click="
                                    removeOption(questionIndex, optionIndex)
                                "
                            >
                                <X />
                            </Button>
                        </div>
                        <InputError
                            :message="
                                fieldError(`questions.${questionIndex}.options`)
                            "
                        />
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            :disabled="question.options.length >= 4"
                            @click="addOption(questionIndex)"
                        >
                            <Plus />
                            Añadir opción
                        </Button>
                    </div>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.questions.length >= 10"
                    @click="addQuestion"
                >
                    <Plus />
                    Añadir pregunta
                </Button>
            </div>

            <div v-if="form.type === 'flashcards'" class="space-y-4">
                <div
                    v-for="(card, cardIndex) in form.flashcards"
                    :key="cardIndex"
                    class="grid gap-3 border-l-2 pl-4 md:grid-cols-[1fr_1fr_auto]"
                >
                    <div class="grid gap-2">
                        <Label :for="`front-${node?.id ?? 'new'}-${cardIndex}`"
                            >Anverso {{ cardIndex + 1 }}</Label
                        >
                        <Input
                            :id="`front-${node?.id ?? 'new'}-${cardIndex}`"
                            v-model="card.front"
                            required
                        />
                        <InputError
                            :message="
                                fieldError(`flashcards.${cardIndex}.front`)
                            "
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`back-${node?.id ?? 'new'}-${cardIndex}`"
                            >Reverso {{ cardIndex + 1 }}</Label
                        >
                        <Input
                            :id="`back-${node?.id ?? 'new'}-${cardIndex}`"
                            v-model="card.back"
                            required
                        />
                        <InputError
                            :message="
                                fieldError(`flashcards.${cardIndex}.back`)
                            "
                        />
                    </div>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        class="self-end"
                        :disabled="form.flashcards.length <= 1"
                        title="Eliminar flashcard"
                        @click="form.flashcards.splice(cardIndex, 1)"
                    >
                        <X />
                    </Button>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.flashcards.length >= 10"
                    @click="addFlashcard"
                >
                    <Plus />
                    Añadir flashcard
                </Button>
            </div>

            <Button type="submit" :disabled="form.processing">
                <Save />
                {{ node ? 'Guardar etapa' : 'Añadir etapa' }}
            </Button>
        </form>
    </section>
</template>
