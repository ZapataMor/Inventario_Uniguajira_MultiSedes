<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    @php
        $backgroundPath = $branding?->login_background ?? 'assets/images/portal-uniguajira.jpeg';
        $backgroundAlt = $branding?->sede_name
            ? "Fondo del login de {$branding->sede_name}"
            : 'Fondo del portal de inventario';
        $appName = $branding?->app_name ?? config('app.name', 'Inventario Uniguajira');
        $sedeName = $branding?->sede_name;
    @endphp
    <body class="min-h-screen bg-white antialiased" data-auth-page="1" data-auth-lock="{{ session('lock_login_back') ? '1' : '0' }}">
        <div class="relative grid h-dvh flex-col items-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="absolute inset-0 -z-20 overflow-hidden">
                <img
                    src="{{ asset($backgroundPath) }}"
                    alt="{{ $backgroundAlt }}"
                    class="h-full w-full object-cover"
                >
            </div>

            <style>
                @keyframes float {
                    0% { transform: translateY(0) rotate(0deg); }
                    50% { transform: translateY(-15px) rotate(5deg); }
                    100% { transform: translateY(0) rotate(0deg); }
                }
            </style>

            <div class="hidden lg:block"></div>

            <div class="flex h-full items-center px-6 py-8 sm:px-10 lg:px-0">
                <div class="mx-auto flex w-full max-w-[640px] flex-col gap-6">
                    <div class="relative isolate w-full max-w-full rounded-3xl p-3 text-white sm:max-w-[560px]">
                        <div class="absolute inset-0 z-0 rounded-3xl bg-white/50 backdrop-blur-sm"></div>
                        <div class="relative z-10 mx-auto w-full space-y-2 px-4 py-4">
                            <h1 class="relative z-10 flex flex-col items-center gap-4 text-center text-3xl font-bold leading-tight sm:flex-row sm:text-left sm:text-4xl lg:text-5xl">
                                <x-app-logo-icon />
                                <span class="min-w-0">
                                    Bienvenido a <br>
                                    <span class="inline-block text-[#ad3728] drop-shadow-lg">
                                        {{ $appName }}
                                    </span>
                                    @if($sedeName)
                                        <br><span class="text-2xl font-semibold text-white/90 sm:text-3xl">{{ $sedeName }}</span>
                                    @endif
                                </span>
                            </h1>
                        </div>
                    </div>

                    <div class="relative bg-neutral-900/65 mx-auto p-3 flex w-full flex-col justify-center space-y-6 sm:w-[400px] rounded-lg">
                        <div class="absolute -left-[65px] -top-[60px] -z-10 h-[180px] w-[180px] rounded-[30%_70%_70%_30%_/_30%_30%_70%_70%] bg-[#ad3728]/60 [animation:float_10s_ease-in-out_infinite]"></div>
                        <div class="absolute -bottom-20 -right-[60px] -z-10 h-[150px] w-[150px] rounded-full bg-[#a3333d]/60 [animation:float_8s_ease-in-out_infinite]"></div>

                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        <script src="{{ asset('assets/js/history-guard.js') }}?v=1"></script>
        @fluxScripts
    </body>
</html>
