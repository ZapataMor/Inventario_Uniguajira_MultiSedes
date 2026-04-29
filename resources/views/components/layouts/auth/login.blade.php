<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    </head>
    @php
        $backgroundPath = $branding?->login_background ?: 'images/fondounigua.png';
        $backgroundAlt = $branding?->sede_name
            ? "Fondo del login de {$branding->sede_name}"
            : 'Fondo del login de Inventario Uniguajira';
    @endphp
    <body
        class="min-h-screen overflow-hidden bg-[#0e0d0c] font-['Plus_Jakarta_Sans',system-ui,sans-serif] text-[#241d1b] antialiased"
        data-auth-page="1"
        data-auth-lock="{{ session('lock_login_back') ? '1' : '0' }}"
    >
        <div class="fixed inset-0 -z-20 overflow-hidden" aria-hidden="true">
            <img
                src="{{ asset($backgroundPath) }}"
                alt="{{ $backgroundAlt }}"
                class="h-full w-full object-cover object-center"
            >
        </div>

        <div
            class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(120%_80%_at_88%_50%,rgba(10,15,25,.32)_0%,rgba(10,15,25,.12)_35%,rgba(0,0,0,0)_60%),linear-gradient(90deg,rgba(0,0,0,0)_50%,rgba(10,15,25,.18)_100%)] max-[900px]:bg-[linear-gradient(180deg,rgba(0,0,0,0)_35%,rgba(10,15,25,.45)_100%)]"
            aria-hidden="true"
        ></div>

        <main class="relative z-[2] grid h-dvh grid-cols-2 items-stretch max-[900px]:grid-cols-1">
            <div class="max-[900px]:hidden" aria-hidden="true"></div>

            <section class="flex items-center justify-center px-8 py-8 max-[900px]:items-center max-[900px]:px-6 max-[900px]:pb-20 max-[900px]:pt-6">
                <div
                    class="relative isolate w-full max-w-[460px] animate-auth-rise rounded-[28px] border border-white/55 bg-[linear-gradient(180deg,rgba(255,255,255,.72)_0%,rgba(255,255,255,.55)_100%)] px-[34px] pb-[30px] pt-[34px] shadow-[0_1px_0_rgba(255,255,255,.7)_inset,0_-1px_0_rgba(255,255,255,.25)_inset,0_30px_80px_-20px_rgba(20,12,8,.45),0_8px_24px_-8px_rgba(20,12,8,.25)] [backdrop-filter:blur(28px)_saturate(140%)] max-[900px]:mx-auto max-[900px]:max-w-[520px]"
                    role="form"
                    aria-label="Iniciar sesión"
                >
                    <div
                        class="pointer-events-none absolute inset-[-1px] -z-10 rounded-[29px] bg-[conic-gradient(from_140deg,rgba(255,255,255,0)_0deg,rgba(255,200,170,.9)_60deg,rgba(255,255,255,.95)_110deg,rgba(255,170,130,.85)_170deg,rgba(255,255,255,0)_220deg,rgba(255,215,150,.75)_300deg,rgba(255,255,255,0)_360deg)] opacity-85"
                        aria-hidden="true"
                    ></div>
                    <div
                        class="pointer-events-none absolute -left-10 -top-10 -z-10 h-60 w-60 rounded-full bg-[radial-gradient(closest-side,rgba(255,210,170,.55),rgba(255,210,170,0)_70%)] blur-xl"
                        aria-hidden="true"
                    ></div>

                    {{ $slot }}
                </div>
            </section>
        </main>

        <p class="fixed bottom-[18px] right-6 z-[3] text-xs text-white/85 drop-shadow-[0_1px_6px_rgba(0,0,0,.45)] max-[900px]:left-6 max-[900px]:right-auto">
            © 2025 Diseñado por semillero SIICS2 — Universidad de La Guajira
        </p>

        <script src="{{ asset('assets/js/history-guard.js') }}?v=1"></script>
        @fluxScripts
    </body>
</html>
