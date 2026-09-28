<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpenCheck,
    ChartNoAxesCombined,
    Layers3,
    Pause,
    Play,
    Route,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { dashboard, login } from '@/routes';

type NavigatorWithConnection = Navigator & {
    connection?: {
        saveData?: boolean;
    };
};

const trailerPath = '/brand/eduquest-trailer.mp4';
const posterPath = '/brand/hero-mundos-1600x900.webp';
const video = ref<HTMLVideoElement | null>(null);
const videoSource = ref<string>();
const isVideoReady = ref(false);
const isVideoPlaying = ref(false);
const hasVideoEnded = ref(false);
let motionPreference: MediaQueryList | undefined;
let availabilityRequest: AbortController | undefined;

const videoControlLabel = computed(() => {
    if (hasVideoEnded.value) {
        return 'Volver a reproducir el tráiler';
    }

    return isVideoPlaying.value ? 'Pausar tráiler' : 'Reanudar tráiler';
});

const disableVideo = () => {
    availabilityRequest?.abort();
    availabilityRequest = undefined;
    video.value?.pause();
    videoSource.value = undefined;
    isVideoReady.value = false;
    isVideoPlaying.value = false;
    hasVideoEnded.value = false;
};

const prepareVideo = async () => {
    const connection = (navigator as NavigatorWithConnection).connection;

    if (motionPreference?.matches || connection?.saveData) {
        disableVideo();

        return;
    }

    availabilityRequest?.abort();
    availabilityRequest = new AbortController();

    try {
        const response = await fetch(trailerPath, {
            method: 'HEAD',
            signal: availabilityRequest.signal,
        });

        if (response.ok) {
            videoSource.value = trailerPath;
        }
    } catch (error) {
        if (!(error instanceof DOMException && error.name === 'AbortError')) {
            disableVideo();
        }
    }
};

const handleMotionPreference = (event: MediaQueryListEvent) => {
    if (event.matches) {
        disableVideo();

        return;
    }

    void prepareVideo();
};

const handleVideoReady = () => {
    isVideoReady.value = true;
    void video.value?.play().catch(() => {
        isVideoPlaying.value = false;
    });
};

const toggleVideo = () => {
    if (!video.value) {
        return;
    }

    if (video.value.paused || video.value.ended) {
        if (video.value.ended) {
            video.value.currentTime = 0;
        }

        hasVideoEnded.value = false;
        void video.value.play();

        return;
    }

    video.value.pause();
};

onMounted(() => {
    motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
    motionPreference.addEventListener('change', handleMotionPreference);
    void prepareVideo();
});

onBeforeUnmount(() => {
    motionPreference?.removeEventListener('change', handleMotionPreference);
    availabilityRequest?.abort();
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
                <video
                    v-if="videoSource"
                    ref="video"
                    :src="videoSource"
                    :poster="posterPath"
                    class="absolute inset-0 size-full object-cover object-center transition-opacity duration-500"
                    :class="isVideoReady ? 'opacity-100' : 'opacity-0'"
                    muted
                    playsinline
                    autoplay
                    preload="metadata"
                    tabindex="-1"
                    @loadeddata="handleVideoReady"
                    @play="isVideoPlaying = true"
                    @pause="isVideoPlaying = false"
                    @ended="
                        isVideoPlaying = false;
                        hasVideoEnded = true;
                    "
                    @error="disableVideo"
                />
                <div
                    class="absolute inset-0 bg-[linear-gradient(90deg,rgba(7,18,35,0.96)_0%,rgba(7,18,35,0.76)_42%,rgba(7,18,35,0.18)_76%),linear-gradient(0deg,rgba(7,18,35,0.8)_0%,transparent_52%)]"
                />
            </div>

            <header
                class="absolute inset-x-0 top-0 z-20 mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-5 py-5 sm:px-8 lg:px-10"
            >
                <button
                    v-if="isVideoReady"
                    type="button"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md border border-white/50 bg-[#101d36]/80 px-3 py-2 text-sm font-semibold text-white backdrop-blur-sm transition-colors hover:border-[#f5b845] hover:text-[#f5b845] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#f5b845] sm:px-4"
                    :aria-label="videoControlLabel"
                    :title="videoControlLabel"
                    @click="toggleVideo"
                >
                    <Pause
                        v-if="isVideoPlaying"
                        class="size-4"
                        aria-hidden="true"
                    />
                    <Play v-else class="size-4" aria-hidden="true" />
                    <span class="hidden sm:inline">{{
                        videoControlLabel
                    }}</span>
                </button>
                <span v-else aria-hidden="true" />
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
                    <Link
                        :href="$page.props.auth.user ? dashboard() : login()"
                        class="mt-8 inline-flex min-h-12 items-center gap-2 rounded-md bg-[#f5b845] px-6 py-3 font-semibold text-[#101d36] shadow-lg shadow-black/20 transition-colors hover:bg-[#ffd16d] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                    >
                        {{
                            $page.props.auth.user
                                ? 'Continuar mi camino'
                                : 'Entrar en EDUQuest'
                        }}
                        <ArrowRight class="size-5" aria-hidden="true" />
                    </Link>
                </div>
            </div>
        </section>

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
