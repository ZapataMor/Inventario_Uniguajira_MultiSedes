<x-layouts.auth.login>
    <div class="text-[11px] font-semibold uppercase tracking-[.32em] text-[#ffb71b]/90">
        Iniciar sesión
    </div>

    <h1 class="mt-2 text-[34px] font-semibold leading-tight tracking-[-.02em] text-white max-sm:text-[30px]">
        Accede a tu cuenta
    </h1>

    <p class="mt-2.5 break-words text-sm leading-6 text-white/60">
        Ingresa con tu correo institucional <span class="break-all font-['JetBrains_Mono',ui-monospace,monospace] text-white/80">@uniguajira.edu.co</span>.
    </p>

    <x-auth-session-status
        class="mt-5 rounded-xl border border-emerald-300/25 bg-emerald-400/10 px-3 py-2 text-center text-sm font-semibold text-emerald-100"
        :status="session('status')"
    />

    <p class="mt-5 hidden rounded-xl border border-rose-300/25 bg-rose-500/10 px-3 py-2 text-sm font-medium text-rose-100" data-auth-general-error></p>

    <form method="POST" action="{{ route('login.store') }}" autocomplete="on" class="mt-7 flex flex-col gap-3 max-sm:mt-6 max-sm:gap-2.5" data-auth-login-form>
        @csrf

        <div>
            <div
                class="group relative rounded-2xl border border-white/10 bg-white/[.03] px-4 pb-2 pt-5 transition-[border-color,background-color,box-shadow,transform] duration-300 hover:border-white/20 focus-within:border-[#ffb71b]/55 focus-within:bg-white/[.06] focus-within:shadow-[0_0_0_4px_rgba(255,183,27,.10),0_0_30px_-6px_rgba(255,183,27,.35)] data-[invalid=true]:border-rose-400/70 data-[invalid=true]:shadow-[0_0_0_4px_rgba(244,63,94,.10)]"
                data-auth-field-shell
                data-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
            >
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-white/55 transition-colors duration-300 group-focus-within:text-[#ffd36b]" aria-hidden="true">
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
                    class="peer w-full border-0 bg-transparent py-1.5 pl-8 pr-1 text-[15px] font-medium leading-tight text-white outline-none placeholder:text-transparent"
                >
                <label
                    for="email"
                    class="pointer-events-none absolute left-12 top-3 text-[11px] font-medium uppercase tracking-[.18em] text-white/60 transition-all duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-[15px] peer-placeholder-shown:normal-case peer-placeholder-shown:tracking-normal peer-placeholder-shown:text-white/55 peer-focus:top-3 peer-focus:translate-y-0 peer-focus:text-[11px] peer-focus:uppercase peer-focus:tracking-[.18em] peer-focus:text-white/60"
                >
                    Correo institucional
                </label>
            </div>
            <p class="{{ $errors->has('email') ? '' : 'hidden' }} mt-1.5 text-xs font-semibold text-rose-200" data-auth-error-for="email">
                @error('email'){{ $message }}@enderror
            </p>
        </div>

        <div>
            <div
                class="group relative rounded-2xl border border-white/10 bg-white/[.03] px-4 pb-2 pt-5 transition-[border-color,background-color,box-shadow,transform] duration-300 hover:border-white/20 focus-within:border-[#ffb71b]/55 focus-within:bg-white/[.06] focus-within:shadow-[0_0_0_4px_rgba(255,183,27,.10),0_0_30px_-6px_rgba(255,183,27,.35)] data-[invalid=true]:border-rose-400/70 data-[invalid=true]:shadow-[0_0_0_4px_rgba(244,63,94,.10)]"
                data-auth-field-shell
                data-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
            >
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-white/55 transition-colors duration-300 group-focus-within:text-[#ffd36b]" aria-hidden="true">
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
                    class="peer w-full border-0 bg-transparent py-1.5 pl-8 pr-10 text-[15px] font-medium leading-tight text-white outline-none placeholder:text-transparent"
                    data-auth-password
                >
                <label
                    for="password"
                    class="pointer-events-none absolute left-12 top-3 text-[11px] font-medium uppercase tracking-[.18em] text-white/60 transition-all duration-300 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-[15px] peer-placeholder-shown:normal-case peer-placeholder-shown:tracking-normal peer-placeholder-shown:text-white/55 peer-focus:top-3 peer-focus:translate-y-0 peer-focus:text-[11px] peer-focus:uppercase peer-focus:tracking-[.18em] peer-focus:text-white/60"
                >
                    Contraseña
                </label>
                <button
                    type="button"
                    class="absolute right-3 top-1/2 grid -translate-y-1/2 rounded-lg p-1.5 text-white/55 transition hover:bg-white/10 hover:text-white"
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
            <p class="{{ $errors->has('password') ? '' : 'hidden' }} mt-1.5 text-xs font-semibold text-rose-200" data-auth-error-for="password">
                @error('password'){{ $message }}@enderror
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 pt-1.5">
            <label class="group flex cursor-pointer select-none items-center gap-2.5 text-[13px] text-white/70 transition hover:text-white">
                <input type="checkbox" name="remember" value="1" class="peer sr-only" @checked(old('remember'))>
                <span class="relative h-5 w-9 rounded-full bg-white/15 transition-colors duration-300 after:absolute after:left-0.5 after:top-0.5 after:size-4 after:rounded-full after:bg-white after:shadow-md after:transition-transform after:duration-300 after:content-[''] peer-checked:bg-[#ffb71b]/80 peer-checked:after:translate-x-4" aria-hidden="true"></span>
                Recordar acceso
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="flex items-center gap-1 text-right text-[13px] font-medium text-white/70 transition hover:text-[#ffb71b] max-[420px]:w-full max-[420px]:justify-start max-[420px]:text-left" wire:navigate>
                    ¿Olvidaste tu contraseña?
                    <svg class="size-3.5 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 6 6 6-6 6"></path>
                    </svg>
                </a>
            @endif
        </div>

        <button
            type="submit"
            class="group relative mt-3 flex w-full cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-2xl bg-[linear-gradient(135deg,#ff3d57_0%,#c8102e_50%,#7a0c1c_100%)] py-3.5 text-[15px] font-medium text-white shadow-[0_1px_0_rgba(255,255,255,.25)_inset,0_-10px_24px_-10px_rgba(255,183,27,.45)_inset,0_18px_40px_-12px_rgba(200,16,46,.55),0_0_0_1px_rgba(255,255,255,.06)] transition-[transform,box-shadow,filter] duration-200 hover:-translate-y-px hover:brightness-[1.06] hover:shadow-[0_1px_0_rgba(255,255,255,.3)_inset,0_-10px_24px_-10px_rgba(255,183,27,.6)_inset,0_22px_50px_-10px_rgba(200,16,46,.7),0_0_0_1px_rgba(255,255,255,.10)] disabled:cursor-wait disabled:opacity-90 data-[state=pressing]:scale-[.975] data-[state=pressing]:brightness-[.96]"
            data-test="login-button"
            data-auth-submit
            data-state="idle"
        >
            <span class="pointer-events-none absolute inset-0 -translate-x-full bg-[linear-gradient(120deg,rgba(255,255,255,0)_30%,rgba(255,255,255,.35)_50%,rgba(255,255,255,0)_70%)] mix-blend-overlay transition-transform duration-[900ms] group-hover:translate-x-full" aria-hidden="true"></span>
            <span class="relative z-10 hidden size-4 rounded-full border-2 border-white/40 border-t-white motion-safe:animate-spin" data-auth-submit-spinner aria-hidden="true"></span>
            <span class="relative z-10" data-auth-submit-text>Iniciar sesión</span>
            <svg class="relative z-10 size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-auth-submit-icon>
                <path d="M5 12h14"></path>
                <path d="m13 6 6 6-6 6"></path>
            </svg>
        </button>

        <div class="my-5 flex items-center gap-3">
            <span class="h-px flex-1 bg-white/10"></span>
            <span class="text-[10px] uppercase tracking-[.32em] text-white/45">o continúa con</span>
            <span class="h-px flex-1 bg-white/10"></span>
        </div>

        <div class="grid grid-cols-2 gap-2.5">
            <button type="button" disabled class="flex min-w-0 items-center justify-center gap-2.5 rounded-xl border border-white/10 bg-white/[.07] px-3 py-3 text-center text-[13px] text-white opacity-70 backdrop-blur-[18px] backdrop-saturate-[140%] transition hover:bg-white/10 disabled:cursor-not-allowed max-[420px]:text-[12px]">
                <svg class="size-[18px]" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21.6 12.2c0-.7-.1-1.3-.2-1.9H12v3.7h5.4a4.6 4.6 0 0 1-2 3.1v2.5h3.2c1.9-1.7 3-4.3 3-7.4Z" fill="#4285F4"></path>
                    <path d="M12 22c2.7 0 5-1 6.6-2.4l-3.2-2.5c-.9.6-2 1-3.4 1-2.6 0-4.8-1.7-5.6-4.1H3.1v2.6A10 10 0 0 0 12 22Z" fill="#34A853"></path>
                    <path d="M6.4 14a6 6 0 0 1 0-3.9V7.5H3.1a10 10 0 0 0 0 9L6.4 14Z" fill="#FBBC05"></path>
                    <path d="M12 5.9c1.5 0 2.8.5 3.8 1.5l2.8-2.8A10 10 0 0 0 3.1 7.5L6.4 10c.8-2.4 3-4.1 5.6-4.1Z" fill="#EA4335"></path>
                </svg>
                Google Workspace
            </button>
            <button type="button" disabled class="flex min-w-0 items-center justify-center gap-2.5 rounded-xl border border-white/10 bg-white/[.07] px-3 py-3 text-center text-[13px] text-white opacity-70 backdrop-blur-[18px] backdrop-saturate-[140%] transition hover:bg-white/10 disabled:cursor-not-allowed max-[420px]:text-[12px]">
                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                    <path d="M14 14h3v3h-3zM20 14v7M14 20h7"></path>
                </svg>
                Carné digital
            </button>
        </div>

        <div class="mt-6 flex items-center justify-between rounded-xl border border-white/10 bg-white/[.07] px-3.5 py-2.5 backdrop-blur-[18px] backdrop-saturate-[140%]">
            <div class="flex min-w-0 items-center gap-2.5 text-[12px] leading-snug text-white/75">
                <svg class="size-4 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"></path>
                    <path d="m9 12 2 2 4-4"></path>
                </svg>
                Conexión cifrada · MFA institucional
            </div>
            <svg class="size-4 text-white/55" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2a10 10 0 0 0-10 10"></path>
                <path d="M12 2a10 10 0 0 1 10 10"></path>
                <path d="M5 12a7 7 0 0 1 14 0v3"></path>
                <path d="M8 12a4 4 0 0 1 8 0v4a4 4 0 0 1-1 2.7"></path>
                <path d="M12 12v5"></path>
                <path d="M11 21c.5-.6 1-1.5 1-3"></path>
            </svg>
        </div>
    </form>

    <div class="mt-6 text-center text-[13px] text-white/55">
        ¿Aún no tienes cuenta?
        <span class="font-medium text-white transition">Solicitar acceso</span>
    </div>
</x-layouts.auth.login>
