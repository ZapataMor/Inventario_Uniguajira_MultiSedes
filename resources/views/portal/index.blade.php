@extends('layouts.app')

@section('title', 'Portal Central - Inventario Uniguajira')

@section('content')
<div class="mx-auto w-full px-4 sm:px-6 lg:px-10">

    {{-- Encabezado del portal --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-800">Portal Central de Inventarios</h1>
    </div>

    {{-- Tarjetas de acceso a sedes --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3 xl:gap-8">
        @foreach($tenants as $tenant)
            @php
                $domain = $tenant->primaryDomain();
                $scheme = request()->isSecure() ? 'https' : 'http';
                $port = request()->getPort();
                $portSuffix = ($port && ! in_array($port, [80, 443])) ? ":{$port}" : '';
                $sedeUrl = $domain ? "{$scheme}://{$domain}{$portSuffix}/login" : route('portal.switch', $tenant->slug);
                $bgImage = $tenant->branding?->login_background;
            @endphp
            <a href="{{ $sedeUrl }}"
               target="_blank"
               rel="noopener noreferrer"
               class="group relative block h-56 overflow-hidden rounded-2xl border border-slate-200 shadow-sm transition-shadow duration-300 hover:shadow-xl">
                @if($bgImage)
                    <img src="{{ asset($bgImage) }}"
                         alt="{{ $tenant->branding?->sede_name ?? $tenant->name }}"
                         class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-110">
                @else
                    <div class="absolute inset-0 bg-gradient-to-br from-slate-300 to-slate-500 transition-transform duration-500 ease-out group-hover:scale-110"></div>
                @endif

                <div class="absolute bottom-0 left-0 right-0 flex h-[20%] items-center justify-center [background-color:color-mix(in_srgb,_oklch(28%_0.08_8)_70%,_transparent)]">
                    <h2 class="text-lg font-bold tracking-wide !text-white">
                        {{ $tenant->branding?->sede_name ?? $tenant->name }}
                    </h2>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
