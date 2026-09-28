<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpenCheck,
    ChartNoAxesCombined,
    Layers3,
    Play,
    Route,
    X,
} from '@lucide/vue';
import { nextTick, onBeforeUnmount, ref } from 'vue';
import { dashboard, login } from '@/routes';

const trailerPath = '/brand/eduquest-trailer.mp4';
const posterPath = '/brand/hero-mundos-1600x900.webp';
const trailerTrigger = ref<HTMLButtonElement | null>(null);
const trailerDialog = ref<HTMLDialogElement | null>(null);
const video = ref<HTMLVideoElement | null>(null);

const stopTrailer = () => {
    if (!video.value) {
        return;
    }

    video.value.pause();
    video.value.removeAttribute('src');
    video.value.load();
};

const openTrailer = () => {
    if (!trailerDialog.value || !video.value) {
        return;
    }

    trailerDialog.value.showModal();
    video.value.src = trailerPath;
    video.value.muted = false;
    video.value.load();
    void video.value.play().catch(() => undefined);
};

const closeTrailer = () => {
    if (trailerDialog.value?.open) {
        trailerDialog.value.close();
    }
};

const handleTrailerClosed = () => {
    stopTrailer();

    void nextTick(() => trailerTrigger.value?.focus());
};

const handleTrailerCancel = (event: Event) => {
    event.preventDefault();
    closeTrailer();
};

onBeforeUnmount(() => {
    stopTrailer();
});
</script>

<template>
    <Head title="Un camino, muchos mundos">
        <meta
            name="description"
            content="EDUQuest convierte el repaso en misiones visuales con actividades, progreso y un camino claro para avanzar."
        />
    </Head>

    <main class="min-h-screen overflow-x-hidden bg-[#f7f9fc] text-[#101d36]">
        <section
            class="relative flex max-h-[58rem] min-h-[calc(100svh-3rem)] overflow-hidden bg-[#101d36] text-white"
        >
            <div data-hero-media class="absolute inset-0" aria-hidden="true">
                <img
                    :src="posterPath"
                    alt=""
                    class="size-full object-cover object-center motion-safe:transition-transform motion-safe:duration-700 lg:hover:scale-[1.01]"
                    fetchpriority="high"
                />
                <div
                    class="absolute inset-0 bg-[linear-gradient(90deg,rgba(7,18,35,0.96)_0%,rgba(7,18,35,0.76)_42%,rgba(7,18,35,0.18)_76%),linear-gradient(0deg,rgba(7,18,35,0.8)_0%,transparent_52%)]"
                />
            </div>

            <header
                class="absolute inset-x-0 top-0 z-20 mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-5 py-5 sm:px-8 lg:px-10"
            >
                <Link
                    href="/"
                    class="rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#f5b845]"
                    aria-label="EDUQuest, inicio"
                >
                    <img
                        src="/brand/logo-horizontal-oscuro.svg"
                        alt="EDUQuest"
                        class="h-9 w-auto sm:h-12"
                    />
                </Link>
                <Link
                    :href="$page.props.auth.user ? dashboard() : login()"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md border border-white/50 bg-[#101d36]/75 px-3 py-2 text-sm font-semibold text-white backdrop-blur-sm transition-colors hover:border-[#f5b845] hover:text-[#f5b845] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#f5b845] sm:px-4"
                >
                    {{ $page.props.auth.user ? 'Ir a mi panel' : 'Acceder' }}
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </header>

            <div
                class="relative z-10 mx-auto flex w-full max-w-7xl items-end px-5 pt-32 pb-14 sm:px-8 sm:pb-20 lg:px-10"
            >
                <div
                    class="max-w-2xl rounded-md bg-[#071223]/72 p-5 shadow-2xl shadow-black/20 backdrop-blur-[2px] sm:p-7"
                >
                    <p
                        class="mb-4 text-sm font-semibold tracking-[0.18em] text-[#f5b845] uppercase sm:text-base"
                    >
                        Un camino, muchos mundos
                    </p>
                    <h1
                        class="text-5xl leading-none font-bold sm:text-6xl lg:text-7xl"
                    >
                        EDUQuest
                    </h1>
                    <p
                        class="mt-6 max-w-xl text-lg leading-8 text-[#e8f0fb] sm:text-xl"
                    >
                        Misiones de repaso creadas por tu docente, actividades
                        en orden y un progreso que siempre puedes retomar.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Link
                            :href="
                                $page.props.auth.user ? dashboard() : login()
                            "
                            class="inline-flex min-h-12 items-center gap-2 rounded-md bg-[#f5b845] px-6 py-3 font-semibold text-[#101d36] shadow-lg shadow-black/20 transition-colors hover:bg-[#ffd16d] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                        >
                            {{
                                $page.props.auth.user
                                    ? 'Continuar mi camino'
                                    : 'Entrar en EDUQuest'
                            }}
                            <ArrowRight class="size-5" aria-hidden="true" />
                        </Link>
                        <button
                            ref="trailerTrigger"
                            type="button"
                            class="inline-flex min-h-12 items-center gap-2 rounded-md border border-white/70 bg-white/12 px-6 py-3 font-semibold text-white backdrop-blur-sm transition-colors hover:border-[#f5b845] hover:bg-[#101d36]/75 hover:text-[#f5b845] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                            @click="openTrailer"
                        >
                            <Play class="size-5" aria-hidden="true" />
                            Ver tráiler
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <dialog
            ref="trailerDialog"
            aria-labelledby="trailer-title"
            aria-describedby="trailer-description"
            class="m-auto w-[min(96vw,80rem)] max-w-none overflow-visible border-0 bg-transparent p-0 text-white backdrop:bg-[#071223]/95 backdrop:backdrop-blur-sm"
            @cancel="handleTrailerCancel"
            @close="handleTrailerClosed"
        >
            <div
                class="relative overflow-hidden rounded-lg border border-white/20 bg-black shadow-2xl"
            >
                <h2 id="trailer-title" class="sr-only">Tráiler de EDUQuest</h2>
                <p id="trailer-description" class="sr-only">
                    Reproductor del tráiler oficial de EDUQuest.
                </p>
                <button
                    type="button"
                    class="absolute top-3 right-3 z-10 inline-flex size-11 items-center justify-center rounded-md border border-white/60 bg-[#071223]/85 text-white shadow-lg backdrop-blur-sm transition-colors hover:border-[#f5b845] hover:text-[#f5b845] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#f5b845]"
                    aria-label="Cerrar tráiler"
                    title="Cerrar tráiler"
                    autofocus
                    @click="closeTrailer"
                >
                    <X class="size-5" aria-hidden="true" />
                </button>
                <video
                    ref="video"
                    :poster="posterPath"
                    class="aspect-video max-h-[88svh] w-full bg-black object-contain"
                    controls
                    playsinline
                    preload="none"
                />
            </div>
        </dialog>

        <section
            aria-labelledby="funciones-actuales"
            class="bg-white py-16 sm:py-20"
        >
            <div class="mx-auto w-full max-w-7xl px-5 sm:px-8 lg:px-10">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-[#167c76] uppercase">
                        Disponible hoy
                    </p>
                    <h2
                        id="funciones-actuales"
                        class="mt-2 text-3xl font-bold sm:text-4xl"
                    >
                        Del contenido al avance, sin perder el hilo
                    </h2>
                    <p class="mt-4 leading-7 text-[#40546d]">
                        El profesorado prepara y asigna las misiones. El
                        alumnado avanza etapa a etapa y conserva sus resultados.
                    </p>
                </div>

                <div
                    class="mt-12 grid gap-10 border-y border-[#d9e2ee] py-10 md:grid-cols-3"
                >
                    <article>
                        <Layers3
                            class="size-7 text-[#a3610d]"
                            aria-hidden="true"
                        />
                        <h3 class="mt-4 text-lg font-semibold">
                            Misiones manuales
                        </h3>
                        <p class="mt-2 leading-7 text-[#40546d]">
                            Explicaciones, vídeos, cuestionarios y flashcards en
                            un recorrido preparado por el docente.
                        </p>
                    </article>
                    <article>
                        <Route
                            class="size-7 text-[#167c76]"
                            aria-hidden="true"
                        />
                        <h3 class="mt-4 text-lg font-semibold">
                            Un camino claro
                        </h3>
                        <p class="mt-2 leading-7 text-[#40546d]">
                            Cada etapa se abre al completar la anterior, con
                            bloqueos comprobados también en el servidor.
                        </p>
                    </article>
                    <article>
                        <ChartNoAxesCombined
                            class="size-7 text-[#315f9c]"
                            aria-hidden="true"
                        />
                        <h3 class="mt-4 text-lg font-semibold">
                            Progreso visible
                        </h3>
                        <p class="mt-2 leading-7 text-[#40546d]">
                            Nodos completados, puntos, intentos y mejor nota se
                            calculan desde la actividad guardada.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="bg-[#e8f0fb] py-16 sm:py-20">
            <div
                class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-5 sm:px-8 md:flex-row md:items-center md:justify-between lg:px-10"
            >
                <div class="max-w-2xl">
                    <BookOpenCheck
                        class="size-8 text-[#167c76]"
                        aria-hidden="true"
                    />
                    <h2 class="mt-4 text-3xl font-bold">
                        Tu siguiente etapa te espera
                    </h2>
                    <p class="mt-3 leading-7 text-[#40546d]">
                        Accede con las credenciales facilitadas por tu centro.
                        No existe registro público.
                    </p>
                </div>
                <Link
                    :href="$page.props.auth.user ? dashboard() : login()"
                    class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-md bg-[#101d36] px-6 py-3 font-semibold text-white transition-colors hover:bg-[#18314c] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#a3610d]"
                >
                    {{ $page.props.auth.user ? 'Abrir mi panel' : 'Acceder' }}
                    <ArrowRight class="size-5" aria-hidden="true" />
                </Link>
            </div>
        </section>

        <footer class="bg-[#101d36] px-5 py-8 text-sm text-[#c9d6e8] sm:px-8">
            <div
                class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4"
            >
                <span>EDUQuest</span>
                <span>Un camino, muchos mundos</span>
            </div>
        </footer>
    </main>
</template>
