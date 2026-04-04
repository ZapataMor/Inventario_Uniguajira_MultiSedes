<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased">
        <div class="relative grid h-dvh flex-col items-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">

            <!-- Background image (dynamic per tenant) -->
            <div class="absolute inset-0 z-[-20] overflow-hidden">
                <img
                    src="{{ asset($branding?->login_background ?? 'assets/images/portal-uniguajira.jpeg') }}"
                    alt="Fondo del portal general"
                    class="h-full w-full object-cover"
                >
            </div>

            <!-- Animación -->
            <style>
            @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(5deg); }
            100% { transform: translateY(0px) rotate(0deg); }
            }
            </style>

            <!-- Left side -->
            <div class="bg-muted relative hidden h-full flex-col p-10 text-white lg:flex dark:border-e dark:border-neutral-800">
                <div class="absolute inset-0 z-[-20] bg-neutral-900/40"></div>

                <!-- Bienvenida y Features -->
                <div class="relative z-20 my-auto p-3 space-y-4">
                    <!-- Figura 1: anclada al bloque de título -->
                    <div class="relative space-y-2">
                        <!-- Figura 1 -->
                        <div style="
                        position:absolute;
                        z-index:-1;
                        opacity:0.6;
                        inset:-10px;
                        background-color:#000;
                        animation:float 12s ease-in-out infinite;
                        "></div>
                        <h1 class="flex items-center text-4xl font-bold leading-tight">
                                <x-app-logo-icon />
                                <span>Bienvenido a <br>
                                    <span class="text-[#ad3728] drop-shadow-lg">
                                        {{ $branding?->app_name ?? config('app.name', 'Laravel') }}
                                    </span>
                                    @if($branding?->sede_name)
                                        <br><span class="text-2xl text-white/90 font-semibold">{{ $branding?->sede_name }}</span>
                                    @endif
                                </span>
                        </h1>
                        <p class="text-sm text-gray-300 leading-relaxed">
                            {{ $branding?->login_welcome_text ?? 'Gestiona el inventario de la Universidad y mantén el control de tus activos.' }}
                        </p>
                    </div>

                    <!-- Feature highlights -->
                    <div class="space-y-4">
                        <x-feature-box
                            title="Registrar Bienes"
                            description="Añade nuevos activos al sistema con facilidad"
                            icon-type="plus-circle"
                        />
                        <x-feature-box
                            title="Administrar Inventarios"
                            description="Organiza bienes por ubicación y estado"
                            icon-type="clipboard-document-check"
                        />
                        <x-feature-box
                            title="Generar Reportes"
                            description="Obtén informes detallados y estadísticas"
                            icon-type="chart-bar"
                        />
                    </div>

                </div>

                <!-- Cita -->
                @php
                    [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
                @endphp
                <div class="absolute bottom-6 z-20 mt-auto">
                    <blockquote class="space-y-1">
                        <flux:heading size="base">&ldquo;{{ trim($message) }}&rdquo;</flux:heading>
                        <footer><flux:heading size="sm">{{ trim($author) }}</flux:heading></footer>
                    </blockquote>
                </div>
            </div>

            <!-- Right side (Login) -->
            <div class="h-full flex items-center">
                <div class="absolute inset-0 z-[-20] bg-neutral-900/30"></div>
                <!-- Login card: referencia para anclar los círculos decorativos -->
                <div class="relative bg-neutral-900/65 mx-auto p-3 flex w-full flex-col justify-center space-y-6 sm:w-[400px] rounded-lg">
                    <!-- Figura 3: anclada a esquina superior-izquierda de la card -->
                    <div style="
                    position:absolute;
                    z-index:-1;
                    opacity:0.6;
                    top:-60px;
                    left:-65px;
                    width:180px;
                    height:180px;
                    border-radius:30% 70% 70% 30% / 30% 30% 70% 70%;
                    background-color:#ad3728;
                    animation:float 10s ease-in-out infinite;
                    "></div>

                    <!-- Figura 2: anclada a esquina inferior-derecha de la card -->
                    <div style="
                    position:absolute;
                    z-index:-1;
                    opacity:0.6;
                    bottom:-80px;
                    right:-60px;
                    width:150px;
                    height:150px;
                    border-radius:50%;
                    background-color:#a3333d;
                    animation:float 8s ease-in-out infinite;
                    "></div>

                    <a class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden">
                        <span class="flex h-9 w-9 items-center justify-center rounded-md">
                            <x-app-logo-icon />
                        </span>
                    </a>
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
