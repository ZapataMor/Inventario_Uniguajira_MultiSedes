<x-layouts.auth.login>
    @php
        $sedeLabel = $branding?->sede_name ?? 'Sede Maicao';
    @endphp

    <div class="relative -mx-3 -mt-2 mb-4 flex justify-center px-3 pb-2 pt-[18px]">
        <img
            src="{{ asset('images/sofia-logo.png') }}"
            alt="SOFIA - Software para la organización de inventarios y activos"
            class="block h-auto w-full max-w-[440px] animate-auth-logo-in [filter:drop-shadow(0_14px_30px_rgba(20,12,8,.22))_drop-shadow(0_2px_6px_rgba(20,12,8,.12))]"
        >
        <span class="absolute -bottom-0.5 left-1/2 h-px w-[120px] -translate-x-1/2 bg-[linear-gradient(90deg,transparent,rgba(0,0,0,.18),transparent)]" aria-hidden="true"></span>
    </div>

    <div class="mb-6 mt-[18px] text-center">
        <div class="mb-2 text-[11px] font-bold uppercase tracking-widest text-[oklch(0.55_0.16_28)]">
            {{ $sedeLabel }} · Uniguajira
        </div>
        <h1 class="font-['Instrument_Serif',serif] text-[34px] font-normal leading-[1.05] text-[oklch(0.18_0.02_30)]">
            Bienvenido <em class="italic text-[oklch(0.55_0.16_28)]">de vuelta</em>
        </h1>
        <p class="mt-1.5 text-sm text-[oklch(0.52_0.012_30)]">
            Inicia sesión para gestionar el inventario de tu sede.
        </p>
    </div>

    <x-auth-session-status
        class="mb-4 rounded-xl border border-emerald-200/80 bg-emerald-50/80 px-3 py-2 text-center text-sm font-semibold text-emerald-700"
        :status="session('status')"
    />

    <form method="POST" action="{{ route('login.store') }}" autocomplete="on" class="flex flex-col gap-3.5" data-auth-login-form>
        @csrf

        <div>
            <div class="relative rounded-[14px] border bg-white/65 px-3.5 pb-2 pt-2.5 shadow-[0_1px_0_rgba(255,255,255,.9)_inset,0_1px_2px_rgba(20,12,8,.04)] transition focus-within:border-[oklch(0.72_0.14_30)] focus-within:bg-white/85 focus-within:shadow-[0_0_0_4px_rgba(232,120,90,.18),0_1px_0_rgba(255,255,255,.9)_inset] {{ $errors->has('email') ? 'border-rose-300' : 'border-white/90' }}">
                <label for="email" class="mb-0.5 block text-[11px] font-semibold uppercase tracking-wide text-[oklch(0.32_0.015_30)]">
                    Usuario
                </label>
                <div class="flex items-center gap-2.5">
                    <svg class="size-[18px] flex-none text-[oklch(0.55_0.16_28)] opacity-85" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 21a8 8 0 1 0-16 0"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input
                        id="email"
                        name="email"
                        type="text"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="tu.usuario o correo institucional"
                        class="w-full border-0 bg-transparent py-1 pb-1.5 text-[15px] font-medium leading-tight text-[oklch(0.18_0.02_30)] outline-none placeholder:text-[oklch(0.62_0.02_30_/_0.7)] placeholder:font-normal"
                    >
                </div>
            </div>
            @error('email')
                <p class="mt-1.5 text-xs font-semibold text-rose-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="relative rounded-[14px] border bg-white/65 px-3.5 pb-2 pt-2.5 shadow-[0_1px_0_rgba(255,255,255,.9)_inset,0_1px_2px_rgba(20,12,8,.04)] transition focus-within:border-[oklch(0.72_0.14_30)] focus-within:bg-white/85 focus-within:shadow-[0_0_0_4px_rgba(232,120,90,.18),0_1px_0_rgba(255,255,255,.9)_inset] {{ $errors->has('password') ? 'border-rose-300' : 'border-white/90' }}">
                <label for="password" class="mb-0.5 block text-[11px] font-semibold uppercase tracking-wide text-[oklch(0.32_0.015_30)]">
                    Contraseña
                </label>
                <div class="flex items-center gap-2.5">
                    <svg class="size-[18px] flex-none text-[oklch(0.55_0.16_28)] opacity-85" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full border-0 bg-transparent py-1 pb-1.5 text-[15px] font-medium leading-tight text-[oklch(0.18_0.02_30)] outline-none placeholder:text-[oklch(0.62_0.02_30_/_0.7)] placeholder:font-normal"
                        data-auth-password
                    >
                    <button
                        type="button"
                        class="grid rounded-lg p-1 text-[oklch(0.52_0.012_30)] transition hover:bg-[rgba(232,120,90,.08)] hover:text-[oklch(0.55_0.16_28)]"
                        aria-label="Mostrar contraseña"
                        aria-pressed="false"
                        data-auth-password-toggle
                    >
                        <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-eye-open>
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg class="hidden size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-eye-closed>
                            <path d="M3 3l18 18"/>
                            <path d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6"/>
                            <path d="M9.9 5.1A10.5 10.5 0 0 1 12 5c6.5 0 10 7 10 7a18.7 18.7 0 0 1-3.2 4.1"/>
                            <path d="M6.1 6.1C3.4 7.9 2 12 2 12s3.5 7 10 7c1.6 0 3-.3 4.3-.8"/>
                        </svg>
                    </button>
                </div>
            </div>
            @error('password')
                <p class="mt-1.5 text-xs font-semibold text-rose-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-0.5 flex items-center justify-between gap-3">
            <label class="flex cursor-pointer select-none items-center gap-2.5 text-[13px] text-[oklch(0.32_0.015_30)]">
                <input type="checkbox" name="remember" value="1" class="peer sr-only" @checked(old('remember'))>
                <span class="grid size-[18px] place-items-center rounded-md border border-black/15 bg-white/70 transition peer-checked:border-[oklch(0.55_0.16_28)] peer-checked:bg-[oklch(0.55_0.16_28)] after:hidden after:h-1.5 after:w-2.5 after:-translate-y-px after:rotate-[-45deg] after:border-b-2 after:border-l-2 after:border-white after:content-[''] peer-checked:after:block" aria-hidden="true"></span>
                Recuérdame
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-right text-[13px] font-semibold text-[oklch(0.55_0.16_28)] hover:underline" wire:navigate>
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <button
            type="submit"
            class="group relative mt-[18px] flex h-[52px] w-full cursor-pointer items-center justify-center gap-2.5 overflow-hidden rounded-[14px] bg-[linear-gradient(180deg,oklch(0.66_0.17_30)_0%,oklch(0.55_0.17_28)_100%)] text-[15px] font-bold text-white shadow-[0_1px_0_rgba(255,255,255,.4)_inset,0_-1px_0_rgba(0,0,0,.15)_inset,0_12px_24px_-10px_rgba(190,70,50,.55),0_4px_10px_-4px_rgba(190,70,50,.4)] transition hover:brightness-[1.04] active:translate-y-px disabled:cursor-wait disabled:opacity-90"
            data-test="login-button"
            data-auth-submit
        >
            <span class="pointer-events-none absolute inset-0 -translate-x-full bg-[linear-gradient(120deg,rgba(255,255,255,0)_30%,rgba(255,255,255,.35)_50%,rgba(255,255,255,0)_70%)] transition-transform duration-[800ms] group-hover:translate-x-full" aria-hidden="true"></span>
            <span class="relative z-10" data-auth-submit-text>Iniciar sesión</span>
            <svg class="relative z-10 size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
        </button>
    </form>

    <script>
        (() => {
            const initLogin = () => {
                const form = document.querySelector('[data-auth-login-form]');

                if (! form || form.dataset.authReady === '1') {
                    return;
                }

                form.dataset.authReady = '1';

                const password = form.querySelector('[data-auth-password]');
                const toggle = form.querySelector('[data-auth-password-toggle]');
                const eyeOpen = form.querySelector('[data-eye-open]');
                const eyeClosed = form.querySelector('[data-eye-closed]');

                toggle?.addEventListener('click', () => {
                    const isVisible = password.type === 'text';

                    password.type = isVisible ? 'password' : 'text';
                    toggle.setAttribute('aria-pressed', String(! isVisible));
                    toggle.setAttribute('aria-label', isVisible ? 'Mostrar contraseña' : 'Ocultar contraseña');
                    eyeOpen.classList.toggle('hidden', ! isVisible);
                    eyeClosed.classList.toggle('hidden', isVisible);
                });

                form.addEventListener('submit', () => {
                    const submit = form.querySelector('[data-auth-submit]');
                    const submitText = form.querySelector('[data-auth-submit-text]');

                    if (! submit || ! submitText) {
                        return;
                    }

                    submit.disabled = true;
                    submitText.textContent = 'Verificando...';
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initLogin);
            } else {
                initLogin();
            }

            document.addEventListener('livewire:navigated', initLogin);
        })();
    </script>
</x-layouts.auth.login>
