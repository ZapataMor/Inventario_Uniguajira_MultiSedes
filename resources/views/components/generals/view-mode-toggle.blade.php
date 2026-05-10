@props([
    'target',
])

<button
    type="button"
    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-100"
    data-view-mode-button
    data-view-mode-toggle="{{ $target }}"
    aria-label="Cambiar tipo de vista"
    aria-pressed="false"
    title="Vista actual: tarjetas. Cambiar a lista"
>
    <i class="fas fa-th-large text-base" data-view-mode-icon="grid" aria-hidden="true"></i>
    <i class="fas fa-list text-base hidden" data-view-mode-icon="list" aria-hidden="true"></i>
    <span class="sr-only" data-view-mode-label>Vista en tarjetas</span>
</button>
