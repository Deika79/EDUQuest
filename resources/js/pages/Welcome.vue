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
const adventureImagePath = '/brand/aventura-entre-mundos.png';
const adventureImageWebpPath = '/brand/aventura-entre-mundos.webp';

const navItems = [
    { href: '#que-es', label: 'Que es' },
    { href: '#funciona', label: 'Como funciona' },
    { href: '#mundos', label: 'Mundos' },
    { href: '#motivacion', label: 'Motivacion' },
    { href: '#ia', label: 'IA revisable' },
];

const worlds = [
    {
        name: 'Fantasia',
        image: '/brand/maps/fantasy_map.png',
        description: 'Un escenario de aventura para misiones narrativas.',
    },
    {
        name: 'Ciencia ficcion',
        image: '/brand/maps/science_map.png',
        description: 'Un mapa espacial para explorar contenidos paso a paso.',
    },
    {
        name: 'Oeste',
        image: '/brand/maps/old_west_map.png',
        description: 'Un camino de frontera para avanzar nodo a nodo.',
    },
];

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
    video.value.load();
    void nextTick(() => video.value?.focus());
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
    <Head title="Convierte el repaso en una aventura">
        <meta
            name="description"
            content="EDUQuest ayuda al profesorado a crear misiones de repaso, asignarlas a una clase y seguir el avance del alumnado."
        />
        <link rel="preload" as="image" :href="adventureImageWebpPath" />
    </Head>

    <main
        class="min-h-screen max-w-full overflow-x-hidden bg-[#f7f9fc] text-[#101d36]"
    >
        <section
            class="relative isolate overflow-hidden bg-[#101d36] text-white"
        >
            <div class="absolute inset-0 -z-10" aria-hidden="true">
                <img
                    :src="posterPath"
                    alt=""
                    class="size-full object-cover object-center opacity-50"
                    fetchpriority="high"
                />
                <div
                    class="absolute inset-0 bg-[linear-gradient(90deg,rgba(7,18,35,0.98)_0%,rgba(7,18,35,0.88)_48%,rgba(7,18,35,0.62)_100%)]"
                />
            </div>

            <header
                class="landing-mobile-bound mx-5 box-border flex w-auto max-w-7xl flex-col gap-4 px-0 py-5 sm:mx-auto sm:w-full sm:px-8 lg:flex-row lg:items-center lg:justify-between lg:px-10"
            >
                <div class="flex min-w-0 items-center justify-between gap-4">
                    <Link
                        href="/"
                        class="rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#f5b845]"
                        aria-label="EDUQuest, inicio"
                    >
                        <img
                            src="/brand/logo-horizontal-oscuro.svg"
                            alt="EDUQuest"
                            class="h-10 w-auto sm:h-12"
                        />
                    </Link>
                </div>

                <nav
                    class="grid min-w-0 grid-cols-2 gap-x-4 gap-y-2 text-sm font-semibold text-[#dce8f7] sm:flex sm:flex-wrap sm:items-center lg:justify-end"
                    aria-label="Secciones de la pagina"
                >
                    <a
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        class="rounded-md px-1 py-1 transition-colors hover:text-[#f5b845] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#f5b845]"
                    >
                        {{ item.label }}
                    </a>
                    <Link
                        :href="$page.props.auth.user ? dashboard() : login()"
                        class="hidden min-h-10 items-center gap-2 rounded-md border border-white/50 bg-[#101d36]/75 px-4 py-2 text-sm font-semibold text-white backdrop-blur-sm transition-colors hover:border-[#f5b845] hover:text-[#f5b845] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#f5b845] lg:inline-flex"
                    >
                        {{
                            $page.props.auth.user ? 'Ir a mi panel' : 'Acceder'
                        }}
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </Link>
                </nav>
                <Link
                    :href="$page.props.auth.user ? dashboard() : login()"
                    class="landing-mobile-fill box-border inline-flex min-h-10 w-full max-w-full items-center justify-center gap-2 rounded-md border border-white/50 bg-[#101d36]/75 px-3 py-2 text-sm font-semibold text-white backdrop-blur-sm transition-colors hover:border-[#f5b845] hover:text-[#f5b845] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#f5b845] sm:w-auto lg:hidden"
                >
                    {{ $page.props.auth.user ? 'Ir a mi panel' : 'Acceder' }}
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </header>

            <div
                class="landing-mobile-bound mx-5 box-border grid w-auto max-w-7xl gap-10 px-0 pt-12 pb-16 sm:mx-auto sm:w-full sm:px-8 sm:pt-16 sm:pb-20 lg:grid-cols-[0.92fr_1.08fr] lg:items-center lg:px-10 lg:pt-20"
            >
                <div
                    class="landing-mobile-bound max-w-2xl min-w-0 max-[639px]:w-[calc(100vw-2.5rem)] max-[639px]:max-w-[calc(100vw-2.5rem)]"
                >
                    <p
                        class="text-sm font-semibold tracking-[0.18em] text-[#f5b845] uppercase"
                    >
                        Un camino, muchos mundos
                    </p>
                    <h1
                        class="mt-4 max-w-4xl text-3xl leading-tight font-bold break-words sm:text-5xl lg:text-6xl"
                    >
                        Convierte el repaso en una aventura
                    </h1>
                    <p
                        class="mt-6 max-w-xl text-lg leading-8 break-words text-[#e8f0fb]"
                    >
                        EDUQuest permite crear misiones educativas con
                        actividades conectadas, mapas visuales y seguimiento del
                        progreso desde una unica aplicacion.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Link
                            :href="
                                $page.props.auth.user ? dashboard() : login()
                            "
                            class="landing-mobile-fill box-border inline-flex min-h-12 w-full max-w-full items-center justify-center gap-2 rounded-md bg-[#f5b845] px-6 py-3 font-semibold text-[#101d36] shadow-lg shadow-black/20 transition-colors hover:bg-[#ffd16d] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white sm:w-auto"
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
                            class="landing-mobile-fill box-border inline-flex min-h-12 w-full max-w-full items-center justify-center gap-2 rounded-md border border-white/70 bg-white/12 px-6 py-3 font-semibold text-white backdrop-blur-sm transition-colors hover:border-[#f5b845] hover:bg-[#101d36]/75 hover:text-[#f5b845] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white sm:w-auto"
                            @click="openTrailer"
                        >
                            <Play class="size-5" aria-hidden="true" />
                            Ver trailer
                        </button>
                    </div>
                    <p class="mt-4 max-w-lg text-sm leading-6 text-[#c9d6e8]">
                        El video se carga solo al abrirlo y se reproduce con
                        controles nativos para pausar, avanzar o cerrarlo.
                    </p>
                </div>

                <figure
                    class="landing-mobile-fill box-border w-full max-w-full min-w-0 overflow-hidden rounded-lg border border-white/18 bg-white/8 p-3 shadow-2xl shadow-black/25 backdrop-blur-sm max-[639px]:w-[calc(100vw-2.5rem)] max-[639px]:max-w-[calc(100vw-2.5rem)]"
                >
                    <picture>
                        <source
                            :srcset="adventureImageWebpPath"
                            type="image/webp"
                        />
                        <img
                            :src="adventureImagePath"
                            alt="Ilustracion de EDUQuest con el logo y seis personajes en escenarios de fantasia, ciencia ficcion y oeste."
                            class="aspect-[4/3] w-full rounded-md object-contain"
                            decoding="async"
                            fetchpriority="high"
                            loading="eager"
                        />
                    </picture>
                    <figcaption class="sr-only">
                        Aventura EDUQuest entre mundos.
                    </figcaption>
                </figure>
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
                <h2 id="trailer-title" class="sr-only">Trailer de EDUQuest</h2>
                <p id="trailer-description" class="sr-only">
                    Reproductor del trailer de EDUQuest con controles nativos.
                </p>
                <button
                    type="button"
                    class="absolute top-3 right-3 z-10 inline-flex size-11 items-center justify-center rounded-md border border-white/60 bg-[#071223]/85 text-white shadow-lg backdrop-blur-sm transition-colors hover:border-[#f5b845] hover:text-[#f5b845] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#f5b845]"
                    aria-label="Cerrar trailer"
                    title="Cerrar trailer"
                    autofocus
                    @click="closeTrailer"
                >
                    <X class="size-5" aria-hidden="true" />
                </button>
                <video
                    ref="video"
                    :poster="posterPath"
                    class="aspect-video max-h-[82svh] w-full bg-black object-contain"
                    controls
                    playsinline
                    preload="none"
                    tabindex="0"
                >
                    <a :href="trailerPath">Abrir el trailer de EDUQuest</a>
                </video>
                <p class="bg-[#071223] px-4 py-3 text-sm text-[#dce8f7]">
                    Si el video no carga, usa el enlace alternativo:
                    <a
                        :href="trailerPath"
                        class="font-semibold text-[#f5b845] underline underline-offset-4"
                    >
                        abrir trailer
                    </a>
                    .
                </p>
            </div>
        </dialog>

        <section id="que-es" class="bg-white py-16 sm:py-20">
            <div
                class="landing-mobile-bound mx-5 box-border w-auto max-w-7xl px-0 sm:mx-auto sm:w-full sm:px-8 lg:px-10"
            >
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold text-[#167c76] uppercase">
                        Que es EDUQuest
                    </p>
                    <h2 class="mt-2 text-2xl font-bold break-words sm:text-4xl">
                        Una forma visual de organizar actividades de repaso
                    </h2>
                    <p class="mt-4 leading-7 text-[#40546d]">
                        El profesor crea o prepara una mision, la asigna a una
                        clase y revisa el avance. El alumno entra en su mision,
                        completa actividades conectadas sobre un mapa y conserva
                        su progreso para continuar despues.
                    </p>
                </div>

                <div class="mt-12 grid gap-8 md:grid-cols-3">
                    <article class="border-t border-[#d9e2ee] pt-6">
                        <Layers3
                            class="size-7 text-[#a3610d]"
                            aria-hidden="true"
                        />
                        <h3 class="mt-4 text-lg font-semibold">
                            Crear y asignar
                        </h3>
                        <p class="mt-2 leading-7 text-[#40546d]">
                            Las misiones reunen explicaciones, videos,
                            cuestionarios y flashcards en un unico recorrido.
                        </p>
                    </article>
                    <article class="border-t border-[#d9e2ee] pt-6">
                        <Route
                            class="size-7 text-[#167c76]"
                            aria-hidden="true"
                        />
                        <h3 class="mt-4 text-lg font-semibold">
                            Avanzar por etapas
                        </h3>
                        <p class="mt-2 leading-7 text-[#40546d]">
                            El primer nodo esta disponible y los siguientes se
                            desbloquean al completar el anterior.
                        </p>
                    </article>
                    <article class="border-t border-[#d9e2ee] pt-6">
                        <ChartNoAxesCombined
                            class="size-7 text-[#315f9c]"
                            aria-hidden="true"
                        />
                        <h3 class="mt-4 text-lg font-semibold">
                            Seguir el progreso
                        </h3>
                        <p class="mt-2 leading-7 text-[#40546d]">
                            El docente consulta nodos completados, puntos,
                            intentos y mejor nota desde datos guardados.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section id="funciona" class="bg-[#e8f0fb] py-16 sm:py-20">
            <div
                class="landing-mobile-bound mx-5 box-border grid w-auto max-w-7xl gap-10 px-0 sm:mx-auto sm:w-full sm:px-8 lg:grid-cols-2 lg:px-10"
            >
                <div>
                    <p class="text-sm font-semibold text-[#167c76] uppercase">
                        Asi funciona para docentes
                    </p>
                    <ol class="mt-6 space-y-4">
                        <li
                            class="rounded-md bg-white p-5 shadow-sm shadow-[#101d36]/5"
                        >
                            <h3 class="font-semibold">
                                1. Crear o generar un borrador
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-[#40546d]">
                                El docente prepara la mision manualmente o pide
                                a la IA un borrador editable.
                            </p>
                        </li>
                        <li
                            class="rounded-md bg-white p-5 shadow-sm shadow-[#101d36]/5"
                        >
                            <h3 class="font-semibold">2. Revisar y asignar</h3>
                            <p class="mt-2 text-sm leading-6 text-[#40546d]">
                                Antes de publicar, revisa contenidos y elige el
                                escenario del mapa de esa mision.
                            </p>
                        </li>
                        <li
                            class="rounded-md bg-white p-5 shadow-sm shadow-[#101d36]/5"
                        >
                            <h3 class="font-semibold">3. Seguir el avance</h3>
                            <p class="mt-2 text-sm leading-6 text-[#40546d]">
                                Consulta el progreso de la clase y el detalle de
                                cada alumno.
                            </p>
                        </li>
                    </ol>
                </div>

                <div>
                    <p class="text-sm font-semibold text-[#a3610d] uppercase">
                        Asi funciona para alumnos
                    </p>
                    <ol class="mt-6 space-y-4">
                        <li
                            class="rounded-md bg-white p-5 shadow-sm shadow-[#101d36]/5"
                        >
                            <h3 class="font-semibold">
                                1. Entrar en la mision
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-[#40546d]">
                                El alumno ve sus misiones activas y abre el mapa
                                correspondiente.
                            </p>
                        </li>
                        <li
                            class="rounded-md bg-white p-5 shadow-sm shadow-[#101d36]/5"
                        >
                            <h3 class="font-semibold">
                                2. Superar actividades
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-[#40546d]">
                                Completa explicaciones, videos, quizzes y
                                flashcards con validacion en servidor.
                            </p>
                        </li>
                        <li
                            class="rounded-md bg-white p-5 shadow-sm shadow-[#101d36]/5"
                        >
                            <h3 class="font-semibold">
                                3. Desbloquear el siguiente nodo
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-[#40546d]">
                                Cada finalizacion valida abre la siguiente etapa
                                del camino.
                            </p>
                        </li>
                    </ol>
                </div>
            </div>
        </section>

        <section id="mundos" class="bg-white py-16 sm:py-20">
            <div
                class="landing-mobile-bound mx-5 box-border w-auto max-w-7xl px-0 sm:mx-auto sm:w-full sm:px-8 lg:px-10"
            >
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold text-[#167c76] uppercase">
                        Explora tres mundos
                    </p>
                    <h2 class="mt-2 text-2xl font-bold break-words sm:text-4xl">
                        El escenario se elige por mision
                    </h2>
                    <p class="mt-4 leading-7 text-[#40546d]">
                        Fantasia, ciencia ficcion y oeste comparten la misma
                        regla de avance: un camino secuencial, estados claros y
                        actividades en orden.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    <article
                        v-for="world in worlds"
                        :key="world.name"
                        class="overflow-hidden rounded-md border border-[#d9e2ee] bg-[#f7f9fc]"
                    >
                        <img
                            :src="world.image"
                            :alt="`Vista previa del mapa de ${world.name}.`"
                            class="h-72 w-full object-cover object-top"
                            loading="lazy"
                        />
                        <div class="p-5">
                            <h3 class="text-lg font-semibold">
                                {{ world.name }}
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-[#40546d]">
                                {{ world.description }}
                            </p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section id="motivacion" class="bg-[#101d36] py-16 text-white sm:py-20">
            <div
                class="landing-mobile-bound mx-5 box-border grid w-auto max-w-7xl gap-10 px-0 sm:mx-auto sm:w-full sm:px-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:px-10"
            >
                <div>
                    <p class="text-sm font-semibold text-[#f5b845] uppercase">
                        Motivacion para seguir
                    </p>
                    <h2 class="mt-2 text-2xl font-bold break-words sm:text-4xl">
                        Recompensas cosmeticas sin cambiar la evaluacion
                    </h2>
                    <p class="mt-4 leading-7 text-[#dce8f7]">
                        Al completar actividades, el alumno puede ganar XP y
                        monedas. El nivel permite comprar apariencias completas
                        para su avatar, pero no altera notas, puntos academicos
                        ni desbloqueos.
                    </p>
                </div>
                <div
                    class="grid gap-4 sm:grid-cols-3"
                    aria-label="Elementos de motivacion implementados"
                >
                    <div class="rounded-md border border-white/16 p-5">
                        <strong class="block text-2xl text-[#f5b845]"
                            >XP</strong
                        >
                        <p class="mt-2 text-sm leading-6 text-[#dce8f7]">
                            Experiencia por primeras finalizaciones validas.
                        </p>
                    </div>
                    <div class="rounded-md border border-white/16 p-5">
                        <strong class="block text-2xl text-[#f5b845]">
                            Monedas
                        </strong>
                        <p class="mt-2 text-sm leading-6 text-[#dce8f7]">
                            Saldo gastable en la tienda cosmetica.
                        </p>
                    </div>
                    <div class="rounded-md border border-white/16 p-5">
                        <strong class="block text-2xl text-[#f5b845]">
                            Avatar
                        </strong>
                        <p class="mt-2 text-sm leading-6 text-[#dce8f7]">
                            Personaje y apariencias completas equipables.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="ia" class="bg-white py-16 sm:py-20">
            <div
                class="landing-mobile-bound mx-5 box-border grid w-auto max-w-7xl gap-10 px-0 sm:mx-auto sm:w-full sm:px-8 lg:grid-cols-[1fr_0.9fr] lg:items-center lg:px-10"
            >
                <div>
                    <p class="text-sm font-semibold text-[#167c76] uppercase">
                        Creacion asistida
                    </p>
                    <h2 class="mt-2 text-2xl font-bold break-words sm:text-4xl">
                        La IA ayuda a empezar, el docente decide publicar
                    </h2>
                    <p class="mt-4 leading-7 text-[#40546d]">
                        EDUQuest puede pedir a la IA un borrador estructurado de
                        mision. Ese contenido queda editable, los videos
                        requieren revision o sustitucion por el docente y nada
                        se publica ni asigna automaticamente.
                    </p>
                </div>
                <div class="rounded-md bg-[#e8f0fb] p-6">
                    <BookOpenCheck
                        class="size-8 text-[#167c76]"
                        aria-hidden="true"
                    />
                    <h3 class="mt-4 text-xl font-semibold">
                        Siempre como borrador revisable
                    </h3>
                    <p class="mt-3 leading-7 text-[#40546d]">
                        La generacion asistida sigue el mismo flujo que una
                        mision manual: revisar, completar, publicar y asignar de
                        forma explicita.
                    </p>
                </div>
            </div>
        </section>

        <section class="bg-[#e8f0fb] py-16 sm:py-20">
            <div
                class="landing-mobile-bound mx-5 box-border flex w-auto max-w-7xl flex-col gap-8 px-0 sm:mx-auto sm:w-full sm:px-8 md:flex-row md:items-center md:justify-between lg:px-10"
            >
                <div class="landing-mobile-bound max-w-2xl">
                    <h2 class="text-2xl font-bold break-words sm:text-3xl">
                        Entra y continua tu camino
                    </h2>
                    <p class="mt-3 leading-7 text-[#40546d]">
                        Accede con una cuenta creada por el centro o por el
                        docente. El registro publico no esta habilitado.
                    </p>
                </div>
                <Link
                    :href="$page.props.auth.user ? dashboard() : login()"
                    class="landing-mobile-fill inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-md bg-[#101d36] px-6 py-3 font-semibold text-white transition-colors hover:bg-[#18314c] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#a3610d]"
                >
                    {{ $page.props.auth.user ? 'Abrir mi panel' : 'Acceder' }}
                    <ArrowRight class="size-5" aria-hidden="true" />
                </Link>
            </div>
        </section>

        <footer class="bg-[#101d36] px-5 py-8 text-sm text-[#c9d6e8] sm:px-8">
            <div
                class="landing-mobile-bound mx-5 box-border flex w-auto max-w-7xl flex-col gap-3 sm:mx-auto sm:w-full sm:flex-row sm:items-center sm:justify-between"
            >
                <span>EDUQuest</span>
                <span>Demo local y documentacion final en preparacion.</span>
            </div>
        </footer>
    </main>
</template>

<style scoped>
@media (max-width: 639px) {
    .landing-mobile-bound {
        box-sizing: border-box;
        width: calc(100vw - 2.5rem) !important;
        max-width: calc(100vw - 2.5rem) !important;
        margin-right: auto !important;
        margin-left: auto !important;
        padding-right: 0 !important;
        padding-left: 0 !important;
    }

    .landing-mobile-fill {
        box-sizing: border-box;
        width: 100% !important;
        max-width: 100% !important;
    }

    .landing-mobile-bound p,
    .landing-mobile-bound h1,
    .landing-mobile-bound h2,
    .landing-mobile-bound h3 {
        overflow-wrap: anywhere;
    }
}
</style>
