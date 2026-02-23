<x-layouts.auth>
    <div class="flex flex-col gap-6 text-center">
        <h2 class="text-xl font-semibold">Registro deshabilitado</h2>
        <p>La creación de cuentas públicas ha sido desactivada porque el sistema es privado. Contacta al administrador para obtener acceso.</p>
        <div class="mt-4">
            <flux:link :href="route('login')" wire:navigate>{{ __('Iniciar sesión') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>
