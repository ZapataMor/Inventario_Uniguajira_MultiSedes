<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Semillero | Inventario Uniguajira</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative m-0 min-h-screen overflow-x-hidden bg-[#090A0F] text-slate-100">
    @php
        $developers = [
            [
                'name' => 'Luis Felipe Zapata Pérez',
                'email' => 'lfelipezapata@uniguajira.edu.co',
                'semester' => '10. semestre',
                'specialization' => 'Desarrollo backend con Laravel y arquitectura API.',
                'strengths' => ['Diseño de bases de datos', 'APIs REST seguras', 'Optimización SQL'],
                'image' => 'assets/images/zapata.png',
                'contacts' => [
                    ['platform' => 'github', 'label' => 'GitHub', 'handle' => '@lfelipezapata', 'url' => 'https://github.com/lfelipezapata'],
                    ['platform' => 'instagram', 'label' => 'Instagram', 'handle' => '@lfelipezapata.dev', 'url' => 'https://instagram.com/lfelipezapata.dev'],
                    ['platform' => 'linkedin', 'label' => 'LinkedIn', 'handle' => '/in/luis-felipe-zapata', 'url' => 'https://www.linkedin.com/in/luis-felipe-zapata'],
                ],
            ],
            [
                'name' => 'Daniel Andres Sierra Torres',
                'email' => 'dandressierra@uniguajira.edu.co',
                'semester' => '9. semestre',
                'specialization' => 'Frontend con Livewire, Volt y Tailwind CSS.',
                'strengths' => ['Interfaces limpias', 'Experiencia de usuario', 'Componentización Blade'],
                'image' => 'assets/images/daniel.png',
                'contacts' => [
                    ['platform' => 'github', 'label' => 'GitHub', 'handle' => '@dandressierra', 'url' => 'https://github.com/dandressierra'],
                    ['platform' => 'instagram', 'label' => 'Instagram', 'handle' => '@dandressierra.dev', 'url' => 'https://instagram.com/dandressierra.dev'],
                    ['platform' => 'linkedin', 'label' => 'LinkedIn', 'handle' => '/in/daniel-andres-sierra', 'url' => 'https://www.linkedin.com/in/daniel-andres-sierra'],
                ],
            ],
            [
                'name' => 'Kevin Hafid Diaz Garcia',
                'email' => 'khafiddiaz@uniguajira.edu.co',
                'semester' => '9. semestre',
                'specialization' => 'Integración de datos y calidad de software.',
                'strengths' => ['Pruebas funcionales', 'Automatización de procesos', 'Análisis de requerimientos'],
                'image' => 'assets/images/kevin.png',
                'contacts' => [
                    ['platform' => 'github', 'label' => 'GitHub', 'handle' => '@khafiddiaz', 'url' => 'https://github.com/khafiddiaz'],
                    ['platform' => 'instagram', 'label' => 'Instagram', 'handle' => '@khafiddiaz.dev', 'url' => 'https://instagram.com/khafiddiaz.dev'],
                    ['platform' => 'linkedin', 'label' => 'LinkedIn', 'handle' => '/in/kevin-hafid-diaz', 'url' => 'https://www.linkedin.com/in/kevin-hafid-diaz'],
                ],
            ],
        ];
    @endphp

    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-[radial-gradient(ellipse_at_bottom,#1B2735_0%,#090A0F_100%)]">
        <div id="stars"></div>
        <div id="stars2"></div>
        <div id="stars3"></div>
    </div>

    <div class="relative z-10 flex w-full flex-col gap-10 pb-10 sm:px-4 lg:px-6">
        <header class="mx-auto flex w-[90vw] max-w-6xl items-center justify-between gap-4 pt-6">
            <a
                href="{{ route('home.index') }}"
                class="inline-flex w-fit items-center gap-2 rounded-xl border border-[#2D3748] bg-[#161B22] px-4 py-2 text-sm font-semibold text-slate-100 shadow-sm transition duration-300 hover:scale-105 hover:bg-[#1F2933]"
            >
                <span aria-hidden="true">←</span>
                <span>Volver al inicio</span>
            </a>
            <img
                src="{{ asset('assets/images/Log3sem.png') }}"
                alt="Logo del semillero"
                class="h-28 w-auto sm:h-36"
            >
        </header>

        <section class="mx-auto w-[90vw] max-w-6xl space-y-6">
            <div class="rounded-3xl border border-[#2D3748] bg-gradient-to-r from-[#161B22] via-[#1F2933] to-[#161B22] px-6 py-8 text-slate-100 shadow-2xl shadow-black/30">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Semillero Tecnológico</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-50 sm:text-4xl">Equipo de desarrollo</h1>
                <p class="mt-3 max-w-3xl text-sm text-slate-300 sm:text-base">
                    Perfiles técnicos del equipo, con enfoque en desarrollo de software, colaboración y mejora continua.
                </p>
            </div>

            <div class="space-y-10 pt-4">
                @foreach ($developers as $developer)
                    <article class="group relative mx-auto w-[90vw] max-w-6xl pt-16 transition duration-300 hover:scale-[1.015]">
                        <div class="pointer-events-none absolute left-1/2 top-0 z-20 -translate-x-1/2 lg:left-8 lg:translate-x-0">
                            <div class="flex h-48 w-48 items-end justify-center rounded-full border border-[#2D3748] bg-gradient-to-br from-[#1F2933] via-[#161B22] to-[#0D1117] p-2 shadow-2xl shadow-black/50 transition duration-300 group-hover:scale-105 sm:h-52 sm:w-52">
                                <img
                                    src="{{ asset($developer['image']) }}"
                                    alt="{{ $developer['name'] }}"
                                    class="h-full w-full object-contain drop-shadow-2xl"
                                >
                            </div>
                        </div>

                        <div class="relative rounded-3xl bg-[#2D3748] p-px transition duration-300 group-hover:bg-[linear-gradient(45deg,#ff0000,#ff7300,#fffb00,#48ff00,#00ffd5,#002bff,#7a00ff,#ff00c8,#ff0000)] group-hover:bg-[length:400%_100%] group-hover:animate-glowing-border">
                            <div class="pointer-events-none absolute -inset-[2px] rounded-[1.7rem] bg-[linear-gradient(45deg,#ff0000,#ff7300,#fffb00,#48ff00,#00ffd5,#002bff,#7a00ff,#ff00c8,#ff0000)] bg-[length:400%_100%] opacity-0 blur-[5px] transition-opacity duration-300 group-hover:opacity-100 group-hover:animate-glowing-border"></div>
                            <div class="relative z-10 overflow-hidden rounded-3xl bg-[#161B22] shadow-2xl shadow-black/40">
                                <div class="absolute inset-y-0 left-0 hidden w-64 bg-gradient-to-b from-[#1F2933] via-[#161B22] to-[#0D1117] lg:block"></div>
                                <div class="relative space-y-6 px-6 pb-8 pt-28 lg:pl-72 lg:pr-10 lg:pt-8">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h2 class="text-2xl font-bold text-slate-50">{{ $developer['name'] }}</h2>
                                        <span class="rounded-full border border-[#2D3748] bg-[#1F2933] px-3 py-1 text-xs font-semibold uppercase tracking-wider text-slate-300">
                                            {{ $developer['semester'] }}
                                        </span>
                                    </div>

                                    <div class="grid gap-4 text-sm text-slate-300 md:grid-cols-2">
                                        <div class="rounded-2xl border border-[#2D3748] bg-[#1F2933] p-4">
                                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Correo</p>
                                            <p class="mt-2 font-medium text-slate-200">{{ $developer['email'] }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-[#2D3748] bg-[#1F2933] p-4">
                                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Especialización</p>
                                            <p class="mt-2 font-medium text-slate-200">{{ $developer['specialization'] }}</p>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Fortalezas</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($developer['strengths'] as $strength)
                                                <span class="rounded-full border border-[#2D3748] bg-[#1F2933] px-3 py-1 text-xs font-semibold text-slate-200">
                                                    {{ $strength }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Contactos</p>
                                        <div class="mt-3 flex flex-wrap gap-3">
                                            @foreach ($developer['contacts'] as $contact)
                                                @php
                                                    $isGithub = $contact['platform'] === 'github';
                                                    $isInstagram = $contact['platform'] === 'instagram';

                                                    $backFaceClass = $isGithub
                                                        ? 'bg-[#181717] text-white'
                                                        : ($isInstagram
                                                            ? 'bg-gradient-to-r from-[#F58529] via-[#DD2A7B] to-[#8134AF] text-white'
                                                            : 'bg-[#0A66C2] text-white');
                                                @endphp

                                                <a
                                                    href="{{ $contact['url'] }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    aria-label="{{ $contact['label'] }} de {{ $developer['name'] }}"
                                                    class="group/contact relative inline-block h-10 min-w-52 [perspective:1000px] [transform-style:preserve-3d]"
                                                >
                                                    <span
                                                        class="absolute inset-0 flex items-center justify-center gap-2 rounded-xl border border-[#2D3748] bg-[#1F2933] px-4 text-xs font-semibold uppercase tracking-wider text-slate-200 transition duration-500 [backface-visibility:hidden] [transform:translateY(0)_rotateX(0deg)] group-hover/contact:opacity-0 group-hover/contact:[transform:translateY(50%)_rotateX(90deg)]"
                                                    >
                                                        <span>{{ $contact['label'] }}</span>
                                                        <span class="text-slate-400 normal-case">{{ $contact['handle'] }}</span>
                                                    </span>

                                                    <span
                                                        class="absolute inset-0 flex items-center justify-center gap-2 rounded-xl px-4 text-xs font-semibold uppercase tracking-wider transition duration-500 [backface-visibility:hidden] [transform:translateY(-50%)_rotateX(90deg)] opacity-0 group-hover/contact:opacity-100 group-hover/contact:[transform:translateY(0)_rotateX(0deg)] {{ $backFaceClass }}"
                                                    >
                                                        @if ($isGithub)
                                                            <svg class="h-4 w-4" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                                                <path d="M8 0C3.58 0 0 3.58 0 8a8 8 0 0 0 5.47 7.59c.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.5-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82a7.62 7.62 0 0 1 4 0c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8 8 0 0 0 16 8c0-4.42-3.58-8-8-8Z"></path>
                                                            </svg>
                                                        @elseif ($isInstagram)
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                                <path d="M7.8 2h8.4A5.8 5.8 0 0 1 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8A5.8 5.8 0 0 1 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2ZM7.6 4A3.6 3.6 0 0 0 4 7.6v8.8A3.6 3.6 0 0 0 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6A3.6 3.6 0 0 0 16.4 4H7.6Zm9.65 1.5a1.15 1.15 0 1 1 0 2.3 1.15 1.15 0 0 1 0-2.3ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"></path>
                                                            </svg>
                                                        @else
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                                <path d="M4.98 3.5A2.48 2.48 0 1 0 5 8.46a2.48 2.48 0 0 0-.02-4.96ZM3 9h4v12H3V9Zm7 0h3.83v1.71h.06c.53-1 1.83-2.06 3.77-2.06 4.03 0 4.77 2.65 4.77 6.09V21h-4v-5.57c0-1.33-.02-3.05-1.86-3.05-1.87 0-2.15 1.46-2.15 2.95V21h-4V9Z"></path>
                                                            </svg>
                                                        @endif
                                                        <span>{{ $contact['label'] }}</span>
                                                    </span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</body>
</html>
