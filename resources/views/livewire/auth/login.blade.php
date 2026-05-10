<x-layouts.auth.login>
    <div class="text-[11px] font-semibold uppercase tracking-[.32em] text-[#2b5cff]">
        Iniciar sesión
    </div>

    <h1 class="mt-2 text-[34px] font-semibold leading-tight tracking-normal text-slate-900 max-sm:text-[30px]">
        Accede a tu cuenta
    </h1>

    <p class="mt-2.5 break-words text-sm leading-6 text-slate-600">
        Ingresa con tu correo institucional <span class="break-all font-['JetBrains_Mono',ui-monospace,monospace] text-slate-800">@uniguajira.edu.co</span>.
    </p>

    <x-auth-session-status
        class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-sm font-semibold text-emerald-700"
        :status="session('status')"
    />

    <p class="mt-5 hidden rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700" data-auth-general-error></p>

    <form method="POST" action="{{ route('login.store') }}" autocomplete="on" class="mt-7 flex flex-col gap-3 max-sm:mt-6 max-sm:gap-2.5" data-auth-login-form>
        @csrf

        <div>
            <div
                class="group relative rounded-2xl border border-slate-900/10 bg-white/70 px-4 pb-2 pt-5 transition-[border-color,background-color,box-shadow,transform] duration-300 hover:border-slate-900/20 hover:bg-white/85 focus-within:border-[#2b5cff]/55 focus-within:bg-white focus-within:shadow-[0_0_0_4px_rgba(43,92,255,.12),0_8px_24px_-8px_rgba(43,92,255,.18)] data-[invalid=true]:border-rose-400/70 data-[invalid=true]:shadow-[0_0_0_4px_rgba(244,63,94,.10)]"
                data-auth-field-shell
                data-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
            >
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 transition-colors duration-300 group-focus-within:text-[#2b5cff]" aria-hidden="true">
                    <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="m3 7 9 6 9-6"></path>
                    </svg>
                </span>
                <input
                    id="email"
                    name="email"
                    type="text"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder=" "
                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    class="peer w-full border-0 bg-transparent py-1.5 pl-8 pr-1 text-[15px] font-medium leading-tight text-slate-900 outline-none placeholder:text-transparent"
                >
                <label
                    for="email"
                    class="pointer-events-none absolute left-12 top-3 text-[11px] font-medium uppercase tracking-[.18em] text-slate-500 transition-all duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-[15px] peer-placeholder-shown:normal-case peer-placeholder-shown:tracking-normal peer-placeholder-shown:text-slate-500 peer-focus:top-3 peer-focus:translate-y-0 peer-focus:text-[11px] peer-focus:uppercase peer-focus:tracking-[.18em] peer-focus:text-slate-600"
                >
                    Correo institucional
                </label>
            </div>
            <p class="{{ $errors->has('email') ? '' : 'hidden' }} mt-1.5 text-xs font-semibold text-rose-600" data-auth-error-for="email">
                @error('email'){{ $message }}@enderror
            </p>
        </div>

        <div>
            <div
                class="group relative rounded-2xl border border-slate-900/10 bg-white/70 px-4 pb-2 pt-5 transition-[border-color,background-color,box-shadow,transform] duration-300 hover:border-slate-900/20 hover:bg-white/85 focus-within:border-[#2b5cff]/55 focus-within:bg-white focus-within:shadow-[0_0_0_4px_rgba(43,92,255,.12),0_8px_24px_-8px_rgba(43,92,255,.18)] data-[invalid=true]:border-rose-400/70 data-[invalid=true]:shadow-[0_0_0_4px_rgba(244,63,94,.10)]"
                data-auth-field-shell
                data-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
            >
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 transition-colors duration-300 group-focus-within:text-[#2b5cff]" aria-hidden="true">
                    <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="11" width="16" height="10" rx="2"></rect>
                        <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
                    </svg>
                </span>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder=" "
                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                    class="peer w-full border-0 bg-transparent py-1.5 pl-8 pr-10 text-[15px] font-medium leading-tight text-slate-900 outline-none placeholder:text-transparent"
                    data-auth-password
                >
                <label
                    for="password"
                    class="pointer-events-none absolute left-12 top-3 text-[11px] font-medium uppercase tracking-[.18em] text-slate-500 transition-all duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-[15px] peer-placeholder-shown:normal-case peer-placeholder-shown:tracking-normal peer-placeholder-shown:text-slate-500 peer-focus:top-3 peer-focus:translate-y-0 peer-focus:text-[11px] peer-focus:uppercase peer-focus:tracking-[.18em] peer-focus:text-slate-600"
                >
                    Contraseña
                </label>
                <button
                    type="button"
                    class="absolute right-3 top-1/2 grid -translate-y-1/2 rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-900/5 hover:text-slate-900"
                    aria-label="Mostrar contraseña"
                    aria-pressed="false"
                    data-auth-password-toggle
                >
                    <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-eye-open>
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg class="hidden size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-eye-closed>
                        <path d="m3 3 18 18"></path>
                        <path d="M10.6 6.1A10.9 10.9 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-3.2 3.9"></path>
                        <path d="M6.6 6.6A17 17 0 0 0 2 12s3.5 6 10 6c1.5 0 2.9-.3 4.1-.8"></path>
                        <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>
                    </svg>
                </button>
            </div>
            <p class="{{ $errors->has('password') ? '' : 'hidden' }} mt-1.5 text-xs font-semibold text-rose-600" data-auth-error-for="password">
                @error('password'){{ $message }}@enderror
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 pt-1.5">
            <label class="group flex cursor-pointer select-none items-center gap-2.5 text-[13px] text-slate-700 transition hover:text-slate-900">
                <input type="checkbox" name="remember" value="1" class="peer sr-only" @checked(old('remember'))>
                <span class="relative h-5 w-9 rounded-full bg-slate-900/15 transition-colors duration-300 after:absolute after:left-0.5 after:top-0.5 after:size-4 after:rounded-full after:bg-white after:shadow-md after:transition-transform after:duration-300 after:content-[''] peer-checked:bg-[#2b5cff] peer-checked:after:translate-x-4" aria-hidden="true"></span>
                Recordar acceso
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="flex items-center gap-1 text-right text-[13px] font-medium text-slate-700 transition hover:text-[#2b5cff] max-[420px]:w-full max-[420px]:justify-start max-[420px]:text-left" wire:navigate>
                    ¿Olvidaste tu contraseña?
                    <svg class="size-3.5 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 6 6 6-6 6"></path>
                    </svg>
                </a>
            @endif
        </div>

        <button
            type="submit"
            class="group relative mt-3 flex w-full cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-2xl bg-[linear-gradient(180deg,#2f63ff_0%,#2447d6_100%)] py-3.5 text-[15px] font-medium text-white shadow-[0_1px_0_rgba(255,255,255,.40)_inset,0_-10px_24px_-10px_rgba(43,92,255,.45)_inset,0_14px_30px_-12px_rgba(43,92,255,.55),0_4px_10px_-2px_rgba(43,92,255,.35),0_0_0_1px_rgba(43,92,255,.40)] transition-[transform,box-shadow,filter] duration-200 hover:-translate-y-px hover:brightness-[1.04] hover:shadow-[0_1px_0_rgba(255,255,255,.45)_inset,0_-10px_24px_-10px_rgba(43,92,255,.55)_inset,0_18px_36px_-10px_rgba(43,92,255,.65),0_6px_14px_-2px_rgba(43,92,255,.45),0_0_0_1px_rgba(43,92,255,.55)] disabled:cursor-wait disabled:opacity-90 data-[state=pressing]:scale-[.98] data-[state=pressing]:brightness-[.97]"
            data-test="login-button"
            data-auth-submit
            data-state="idle"
        >
            <span class="pointer-events-none absolute inset-0 -translate-x-full bg-[linear-gradient(120deg,rgba(255,255,255,0)_30%,rgba(255,255,255,.55)_50%,rgba(255,255,255,0)_70%)] mix-blend-overlay transition-transform duration-[900ms] group-hover:translate-x-full" aria-hidden="true"></span>
            <span class="relative z-10 hidden size-4 rounded-full border-2 border-white/40 border-t-white motion-safe:animate-spin" data-auth-submit-spinner aria-hidden="true"></span>
            <span class="relative z-10" data-auth-submit-text>Iniciar sesión</span>
            <svg class="relative z-10 size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-auth-submit-icon>
                <path d="M5 12h14"></path>
                <path d="m13 6 6 6-6 6"></path>
            </svg>
        </button>

        <div class="my-5 flex items-center gap-3">
            <span class="h-px flex-1 bg-slate-900/10"></span>
            <span class="text-[10px] uppercase tracking-[.32em] text-slate-500">o continúa con</span>
            <span class="h-px flex-1 bg-slate-900/10"></span>
        </div>

        <div class="grid grid-cols-2 gap-2.5">
            <button type="button" disabled class="flex min-w-0 items-center justify-center gap-2.5 rounded-xl border border-white/85 bg-white/65 px-3 py-3 text-center text-[13px] text-slate-800 opacity-70 shadow-[0_1px_0_rgba(255,255,255,.9)_inset,0_4px_14px_-6px_rgba(20,28,60,.08)] backdrop-blur-[18px] backdrop-saturate-[160%] transition hover:bg-white disabled:cursor-not-allowed max-[420px]:text-[12px]">
                <svg class="size-[18px]" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21.6 12.2c0-.7-.1-1.3-.2-1.9H12v3.7h5.4a4.6 4.6 0 0 1-2 3.1v2.5h3.2c1.9-1.7 3-4.3 3-7.4Z" fill="#4285F4"></path>
                    <path d="M12 22c2.7 0 5-1 6.6-2.4l-3.2-2.5c-.9.6-2 1-3.4 1-2.6 0-4.8-1.7-5.6-4.1H3.1v2.6A10 10 0 0 0 12 22Z" fill="#34A853"></path>
                    <path d="M6.4 14a6 6 0 0 1 0-3.9V7.5H3.1a10 10 0 0 0 0 9L6.4 14Z" fill="#FBBC05"></path>
                    <path d="M12 5.9c1.5 0 2.8.5 3.8 1.5l2.8-2.8A10 10 0 0 0 3.1 7.5L6.4 10c.8-2.4 3-4.1 5.6-4.1Z" fill="#EA4335"></path>
                </svg>
                Google Workspace
            </button>
            <button type="button" disabled class="flex min-w-0 items-center justify-center gap-2.5 rounded-xl border border-white/85 bg-white/65 px-3 py-3 text-center text-[13px] text-slate-800 opacity-70 shadow-[0_1px_0_rgba(255,255,255,.9)_inset,0_4px_14px_-6px_rgba(20,28,60,.08)] backdrop-blur-[18px] backdrop-saturate-[160%] transition hover:bg-white disabled:cursor-not-allowed max-[420px]:text-[12px]">
                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                    <path d="M14 14h3v3h-3zM20 14v7M14 20h7"></path>
                </svg>
                Carné digital
            </button>
        </div>

        <div class="mt-6 flex items-center justify-between rounded-xl border border-white/85 bg-white/65 px-3.5 py-2.5 shadow-[0_1px_0_rgba(255,255,255,.9)_inset,0_4px_14px_-6px_rgba(20,28,60,.08)] backdrop-blur-[18px] backdrop-saturate-[160%]">
            <div class="flex min-w-0 items-center gap-2.5 text-[12px] leading-snug text-slate-700">
                <svg class="size-4 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"></path>
                    <path d="m9 12 2 2 4-4"></path>
                </svg>
                Conexión cifrada · MFA institucional
            </div>
            <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2a10 10 0 0 0-10 10"></path>
                <path d="M12 2a10 10 0 0 1 10 10"></path>
                <path d="M5 12a7 7 0 0 1 14 0v3"></path>
                <path d="M8 12a4 4 0 0 1 8 0v4a4 4 0 0 1-1 2.7"></path>
                <path d="M12 12v5"></path>
                <path d="M11 21c.5-.6 1-1.5 1-3"></path>
            </svg>
        </div>
    </form>

    <div class="mt-6 text-center text-[13px] text-slate-500">
        ¿Aún no tienes cuenta?
        <span class="font-medium text-slate-900 transition">Solicitar acceso</span>
    </div>
</x-layouts.auth.login>
