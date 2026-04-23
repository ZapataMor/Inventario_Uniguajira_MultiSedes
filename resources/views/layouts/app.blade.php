<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $branding?->app_name ?? config('app.name', 'Inventario Uniguajira'))</title>
    <link rel="icon" href="{{ asset($branding?->favicon ?? 'assets/images/favicon-uniguajira-32x32.webp') }}" type="image/png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/get.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/get.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/responsive/get.css') }}">
    @stack('styles')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white">
    @include('layouts.navbar')
    @include('layouts.sidebar')

    <main id="main" class="relative isolate min-h-[calc(100vh-4.8rem)]">
        <div
            class="pointer-events-none absolute inset-0 z-0 [background-image:linear-gradient(45deg,transparent_49%,#e5e7eb_49%,#e5e7eb_51%,transparent_51%),linear-gradient(-45deg,transparent_49%,#e5e7eb_49%,#e5e7eb_51%,transparent_51%)] [background-size:40px_40px] [-webkit-mask-image:radial-gradient(ellipse_80%_80%_at_100%_100%,#000_50%,transparent_90%)] [mask-image:radial-gradient(ellipse_80%_80%_at_100%_100%,#000_50%,transparent_90%)]"
            aria-hidden="true">
        </div>
        <div id="toastContainer" class="toast-container"></div>
        {{-- loader shown while AJAX content loads --}}
        <div id="loader" class="loader"></div>
        <div id="main-content" class="relative z-10">
            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

    @php
        $assetVersion = static function (string $path): int {
            $fullPath = public_path($path);
            $modifiedAt = is_file($fullPath) ? filemtime($fullPath) : false;

            return $modifiedAt ?: time();
        };
    @endphp

    <script src="{{ asset('assets/js/sidebar.js') }}"></script>
    <script src="{{ asset('assets/js/navbar.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/submitForm.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/delete.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/search.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/toast.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/modal.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/selection.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/autocomplete.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/excel-ui.js') }}?v=1"></script>
    <script src="{{ asset('assets/js/history-guard.js') }}?v=1"></script>
    <script src="{{ asset('assets/js/tasks.js') }}"></script>

    <script src="{{ asset('assets/js/goods.js') }}?v=3"></script>
    @if(Auth::user()->isAdministrator())
        <script src="{{ asset('assets/js/goods-excel-upload.js') }}?v=3"></script>
        <script src="{{ asset('assets/js/goods-excel-upload-global.js') }}?v=5"></script>
    @endif

    <script src="{{ asset('assets/js/user.js') }}"></script>
    <script src="{{ asset('assets/js/profile.js') }}"></script>
    <script src="{{ asset('assets/js/inventory/inventory.js') }}"></script>
    <script src="{{ asset('assets/js/inventory/groups.js') }}"></script>
    <script src="{{ asset('assets/js/inventory/goodsInventory.js') }}?v={{ $assetVersion('assets/js/inventory/goodsInventory.js') }}"></script>
    @if(Auth::user()->isAdministrator())
        <script src="{{ asset('assets/js/inventory/goods-inventory-excel-upload.js') }}?v=6"></script>
    @endif
    <script src="{{ asset('assets/js/inventory/goodsSerialsInventory.js') }}?v={{ $assetVersion('assets/js/inventory/goodsSerialsInventory.js') }}"></script>
    <script src="{{ asset('assets/js/inventory/formGoodInventory.js') }}"></script>
    <script src="{{ asset('assets/js/reports/folders.js') }}"></script>
    <script src="{{ asset('assets/js/reports/reports.js') }}"></script>
    <script src="{{ asset('assets/js/records/historial.js') }}"></script>
    <script src="{{ asset('assets/js/inventory/removedGoods.js') }}"></script>  {{-- ✅ AGREGADO --}}
    {{-- <script src="{{ asset('assets/js/onLoaded.js') }}"></script> --}}
    @stack('scripts')
</body>
</html>
