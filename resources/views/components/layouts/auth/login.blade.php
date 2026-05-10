<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    </head>
    @php
        $backgroundPath = ($branding ?? null)?->login_background ?: 'images/fondo-actualizado.png';
        $backgroundAlt = ($branding ?? null)?->sede_name
            ? "Fondo del login de {$branding->sede_name}"
            : 'Fondo institucional';
        $twoFactorUrl = \Illuminate\Support\Facades\Route::has('two-factor.login')
            ? route('two-factor.login')
            : route('login');
    @endphp
    <body
        class="min-h-screen overflow-hidden bg-[#f6f7fb] font-['Inter',ui-sans-serif,system-ui,sans-serif] text-slate-900 antialiased"
        data-auth-page="1"
        data-auth-lock="{{ session('lock_login_back') ? '1' : '0' }}"
    >
        <div
            class="relative h-screen w-screen overflow-hidden bg-[#f6f7fb]"
            data-auth-root
            data-auth-home-url="{{ route('home.index') }}"
            data-auth-two-factor-url="{{ $twoFactorUrl }}"
        >
            <div
                class="auth-stage absolute inset-0 origin-[var(--auth-zoom-x,50%)_var(--auth-zoom-y,50%)] transition-[filter,transform,opacity] duration-[1100ms] ease-[cubic-bezier(.65,0,.35,1)] will-change-[transform,filter,opacity] data-[phase=processing]:brightness-[1.02] data-[phase=processing]:saturate-[.98] data-[phase=zooming]:scale-[1.45] data-[phase=zooming]:blur-[22px] data-[phase=zooming]:brightness-[1.06] data-[phase=zooming]:saturate-[.9] data-[phase=zooming]:opacity-0"
                data-auth-stage
                data-phase="idle"
            >
                <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
                    <img
                        src="{{ asset($backgroundPath) }}"
                        alt="{{ $backgroundAlt }}"
                        class="h-full w-full scale-[1.06] object-cover object-[center_35%] opacity-90 saturate-[.95] contrast-[1.06] motion-safe:animate-auth-slow-pan"
                    >
                    <div class="absolute inset-0 bg-[radial-gradient(120%_90%_at_80%_0%,rgba(255,255,255,.70)_0%,rgba(243,245,250,.58)_45%,rgba(234,238,246,.52)_100%)]"></div>
                    <div class="absolute inset-0 bg-[linear-gradient(110deg,rgba(255,255,255,.78)_0%,rgba(255,255,255,.54)_34%,rgba(255,255,255,.26)_60%,rgba(246,248,252,.60)_100%)]"></div>
                    <div class="absolute inset-0 opacity-90 [background-image:linear-gradient(to_right,rgba(13,20,40,.045)_1px,transparent_1px),linear-gradient(to_bottom,rgba(13,20,40,.045)_1px,transparent_1px)] [background-size:56px_56px] [-webkit-mask-image:radial-gradient(70%_60%_at_50%_40%,black_30%,transparent_80%)] [mask-image:radial-gradient(70%_60%_at_50%_40%,black_30%,transparent_80%)]"></div>
                    <div class="absolute -inset-[10%] bg-[radial-gradient(45%_35%_at_18%_28%,rgba(43,92,255,.18)_0%,rgba(43,92,255,0)_60%),radial-gradient(40%_30%_at_82%_22%,rgba(255,183,27,.14)_0%,rgba(255,183,27,0)_60%),radial-gradient(50%_40%_at_65%_95%,rgba(200,16,46,.10)_0%,rgba(200,16,46,0)_65%),radial-gradient(40%_30%_at_12%_90%,rgba(58,166,255,.14)_0%,rgba(58,166,255,0)_65%)] blur-[.5px] motion-safe:animate-auth-slow-pan"></div>
                    <div class="absolute inset-0 bg-[conic-gradient(from_220deg_at_70%_40%,rgba(43,92,255,.10),rgba(255,183,27,.06),rgba(58,166,255,.10),rgba(255,255,255,0)_60%)] opacity-85 blur-[60px] saturate-[1.05] motion-safe:animate-auth-orb-drift"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(120%_80%_at_50%_50%,rgba(255,255,255,0)_55%,rgba(13,20,40,.06)_100%)]"></div>

                    <div class="absolute inset-0 overflow-hidden max-lg:hidden">
                        <span class="absolute left-[8%] top-[86%] size-1 rounded-full bg-[radial-gradient(circle,rgba(43,92,255,.55)_0%,rgba(43,92,255,.10)_60%,transparent_70%)] opacity-0 blur-[.4px] motion-safe:animate-auth-particle-drift [--auth-duration:12s] [--auth-dx:84px] [--auth-dy:-180px] [animation-delay:-2s]"></span>
                        <span class="absolute left-[14%] top-[74%] size-0.5 rounded-full bg-[radial-gradient(circle,rgba(43,92,255,.55)_0%,rgba(43,92,255,.10)_60%,transparent_70%)] opacity-0 blur-[.4px] motion-safe:animate-auth-particle-drift [--auth-duration:17s] [--auth-dx:-72px] [--auth-dy:-240px] [animation-delay:-8s]"></span>
                        <span class="absolute left-[24%] top-[92%] size-[5px] rounded-full bg-[radial-gradient(circle,rgba(43,92,255,.55)_0%,rgba(43,92,255,.10)_60%,transparent_70%)] opacity-0 blur-[.4px] motion-safe:animate-auth-particle-drift [--auth-duration:20s] [--auth-dx:120px] [--auth-dy:-260px] [animation-delay:-11s]"></span>
                        <span class="absolute left-[36%] top-[70%] size-1 rounded-full bg-[radial-gradient(circle,rgba(43,92,255,.55)_0%,rgba(43,92,255,.10)_60%,transparent_70%)] opacity-0 blur-[.4px] motion-safe:animate-auth-particle-drift [--auth-duration:13s] [--auth-dx:-110px] [--auth-dy:-190px] [animation-delay:-4s]"></span>
                        <span class="absolute left-[47%] top-[88%] size-[3px] rounded-full bg-[radial-gradient(circle,rgba(43,92,255,.55)_0%,rgba(43,92,255,.10)_60%,transparent_70%)] opacity-0 blur-[.4px] motion-safe:animate-auth-particle-drift [--auth-duration:22s] [--auth-dx:90px] [--auth-dy:-300px] [animation-delay:-15s]"></span>
                        <span class="absolute left-[58%] top-[76%] size-1 rounded-full bg-[radial-gradient(circle,rgba(43,92,255,.55)_0%,rgba(43,92,255,.10)_60%,transparent_70%)] opacity-0 blur-[.4px] motion-safe:animate-auth-particle-drift [--auth-duration:16s] [--auth-dx:-140px] [--auth-dy:-210px] [animation-delay:-6s]"></span>
                        <span class="absolute left-[66%] top-[90%] size-[5px] rounded-full bg-[radial-gradient(circle,rgba(43,92,255,.55)_0%,rgba(43,92,255,.10)_60%,transparent_70%)] opacity-0 blur-[.4px] motion-safe:animate-auth-particle-drift [--auth-duration:18s] [--auth-dx:64px] [--auth-dy:-250px] [animation-delay:-9s]"></span>
                        <span class="absolute left-[74%] top-[68%] size-[3px] rounded-full bg-[radial-gradient(circle,rgba(43,92,255,.55)_0%,rgba(43,92,255,.10)_60%,transparent_70%)] opacity-0 blur-[.4px] motion-safe:animate-auth-particle-drift [--auth-duration:14s] [--auth-dx:-98px] [--auth-dy:-170px] [animation-delay:-5s]"></span>
                        <span class="absolute left-[83%] top-[82%] size-1 rounded-full bg-[radial-gradient(circle,rgba(43,92,255,.55)_0%,rgba(43,92,255,.10)_60%,transparent_70%)] opacity-0 blur-[.4px] motion-safe:animate-auth-particle-drift [--auth-duration:19s] [--auth-dx:106px] [--auth-dy:-280px] [animation-delay:-13s]"></span>
                        <span class="absolute left-[91%] top-[72%] size-0.5 rounded-full bg-[radial-gradient(circle,rgba(43,92,255,.55)_0%,rgba(43,92,255,.10)_60%,transparent_70%)] opacity-0 blur-[.4px] motion-safe:animate-auth-particle-drift [--auth-duration:15s] [--auth-dx:-86px] [--auth-dy:-220px] [animation-delay:-7s]"></span>
                    </div>

                    <div class="absolute inset-0 opacity-[.06] mix-blend-multiply [background-image:radial-gradient(circle_at_20%_30%,rgba(20,28,60,.18)_0_1px,transparent_1px),radial-gradient(circle_at_72%_62%,rgba(20,28,60,.14)_0_1px,transparent_1px)] [background-size:7px_7px,11px_11px]"></div>
                </div>

                <main class="relative z-10 grid h-full w-full grid-cols-12">
                    <section class="col-span-12 flex items-center justify-center p-5 sm:p-6 lg:order-1 lg:col-span-5 lg:p-10 xl:col-span-4 max-sm:overflow-y-auto max-sm:py-6">
                        <div class="w-full max-w-[440px] max-sm:max-w-none">
                            <div class="mb-5 flex justify-center motion-safe:animate-auth-rise">
                                <img
                                    src="{{ asset('images/sofia-transparent.png') }}"
                                    alt="SOFIA"
                                    class="h-auto w-[280px] max-w-[86vw] drop-shadow-[0_16px_28px_rgba(20,28,60,.16)] sm:w-[320px]"
                                >
                            </div>

                            <div
                                class="relative isolate w-full rounded-[28px] border border-white/85 bg-white/80 p-6 shadow-[0_1px_0_rgba(255,255,255,1)_inset,0_-40px_80px_-50px_rgba(43,92,255,.10)_inset,0_30px_60px_-25px_rgba(20,28,60,.18),0_8px_20px_-8px_rgba(20,28,60,.10),0_0_0_1px_rgba(20,28,60,.03)] backdrop-blur-[28px] backdrop-saturate-[180%] sm:p-9 max-sm:w-[calc(100vw-2.5rem)] max-sm:max-w-[350px] max-sm:p-5 motion-safe:animate-auth-rise"
                                data-auth-card
                                role="form"
                                aria-label="Iniciar sesión"
                            >
                                <div class="pointer-events-none absolute inset-0 rounded-[28px] opacity-80 bg-[radial-gradient(220px_180px_at_var(--auth-mx,30%)_var(--auth-my,20%),rgba(43,92,255,.10),transparent_60%)]" aria-hidden="true"></div>
                                <div class="absolute -left-3 -top-3 hidden items-center gap-2 rounded-full border border-white/85 bg-white/65 px-3 py-1.5 text-[10px] uppercase tracking-[.26em] text-slate-700 shadow-[0_14px_30px_-18px_rgba(20,28,60,.20)] backdrop-blur-[18px] backdrop-saturate-[160%] sm:flex">
                                    <span class="size-1.5 rounded-full bg-[#2b5cff]"></span>
                                    Portal único
                                </div>
                                <div class="relative">
                                    {{ $slot }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="relative order-2 col-span-7 hidden flex-col justify-between p-10 lg:flex lg:p-14 xl:col-span-8">
                        <div aria-hidden="true"></div>

                        <div class="max-w-[640px] motion-safe:animate-auth-rise [animation-delay:.12s]">
                            <div class="relative inline-block">
                                <div class="pointer-events-none absolute -inset-10 rounded-[40px] bg-[#2b5cff]/10 opacity-80 blur-3xl"></div>
                                <div class="pointer-events-none absolute -inset-12 rounded-[40px] bg-[#ffb71b]/10 opacity-50 blur-3xl"></div>
                                <img
                                    src="{{ asset('images/sofia-transparent.png') }}"
                                    alt="SOFIA - Software para la organización de inventarios y activos"
                                    class="relative block h-auto w-[460px] max-w-full drop-shadow-[0_20px_30px_rgba(20,28,60,.10)] xl:w-[560px]"
                                    data-auth-sofia-logo
                                >
                            </div>
                            <p class="mt-6 max-w-[520px] text-[16px] leading-relaxed text-slate-700 xl:text-[17px]">
                                Una experiencia inteligente para estudiantes, docentes y administrativos, construida sobre la identidad <span class="font-medium text-slate-900">Wayúu</span> y el rigor académico de la Universidad de La Guajira.
                            </p>
                            <div class="mt-10 grid max-w-[520px] grid-cols-3 gap-3">
                                <div class="rounded-2xl border border-white/85 bg-white/65 px-4 py-3.5 shadow-[0_1px_0_rgba(255,255,255,.9)_inset,0_4px_14px_-6px_rgba(20,28,60,.08)] backdrop-blur-[18px] backdrop-saturate-[160%]">
                                    <div class="text-[30px] font-semibold leading-none tracking-normal text-slate-900">30</div>
                                    <div class="mt-1 text-[11px] uppercase tracking-[.16em] text-slate-500">años de historia</div>
                                </div>
                                <div class="rounded-2xl border border-white/85 bg-white/65 px-4 py-3.5 shadow-[0_1px_0_rgba(255,255,255,.9)_inset,0_4px_14px_-6px_rgba(20,28,60,.08)] backdrop-blur-[18px] backdrop-saturate-[160%]">
                                    <div class="text-[30px] font-semibold leading-none tracking-normal text-slate-900">24K</div>
                                    <div class="mt-1 text-[11px] uppercase tracking-[.16em] text-slate-500">comunidad activa</div>
                                </div>
                                <div class="rounded-2xl border border-white/85 bg-white/65 px-4 py-3.5 shadow-[0_1px_0_rgba(255,255,255,.9)_inset,0_4px_14px_-6px_rgba(20,28,60,.08)] backdrop-blur-[18px] backdrop-saturate-[160%]">
                                    <div class="text-[30px] font-semibold leading-none tracking-normal text-slate-900">3</div>
                                    <div class="mt-1 text-[11px] uppercase tracking-[.16em] text-slate-500">sedes conectadas</div>
                                </div>
                            </div>
                        </div>

                        <footer class="flex items-end justify-between text-[12px] text-slate-500 motion-safe:animate-auth-rise [animation-delay:.22s]">
                            <div class="flex items-center gap-6">
                                <div class="font-['JetBrains_Mono',ui-monospace,monospace]">
                                    <div class="text-[14px] text-slate-800"><span data-auth-time>--:--</span><span class="motion-safe:animate-auth-caret">_</span></div>
                                    <div class="capitalize" data-auth-date>{{ now()->locale('es_CO')->translatedFormat('l, d \d\e F') }}</div>
                                </div>
                                <div class="hidden h-9 w-px bg-slate-900/15 md:block"></div>
                                <div class="hidden max-w-xs leading-snug text-slate-500/80 md:block">
                                    Riohacha · La Guajira, Colombia
                                    <div class="text-slate-500/70">11.5444° N · 72.9072° W</div>
                                </div>
                            </div>
                        </footer>

                        <div class="absolute right-0 top-1/2 hidden -translate-y-1/2 translate-x-1/2 flex-col items-center gap-5 xl:flex" aria-hidden="true">
                            <div class="rotate-90 text-[10px] uppercase tracking-[.4em] text-slate-500">Acceso seguro</div>
                            <div class="h-24 w-px bg-gradient-to-b from-transparent via-slate-900/20 to-transparent"></div>
                            <div class="grid size-9 place-items-center rounded-full border border-white/85 bg-white/65 text-[#2b5cff] shadow-[0_1px_0_rgba(255,255,255,.9)_inset,0_4px_14px_-6px_rgba(20,28,60,.08)] backdrop-blur-[18px] backdrop-saturate-[160%]">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                            </div>
                            <div class="h-24 w-px bg-gradient-to-b from-transparent via-slate-900/20 to-transparent"></div>
                            <div class="rotate-90 text-[10px] uppercase tracking-[.4em] text-slate-500">SSO · MFA</div>
                        </div>
                    </section>
                </main>
            </div>

            <div
                class="pointer-events-none fixed inset-0 z-40 overflow-hidden bg-[radial-gradient(60%_50%_at_50%_45%,#ffffff_0%,#f4f7fc_50%,#e9eef7_100%)] opacity-0 transition-opacity duration-[900ms] ease-[cubic-bezier(.65,0,.35,1)] data-[active=true]:pointer-events-auto data-[active=true]:opacity-100"
                data-auth-veil
                data-active="false"
                aria-hidden="true"
            >
                <div class="absolute -inset-[10%] bg-[radial-gradient(35%_30%_at_30%_35%,rgba(43,92,255,.18),transparent_70%),radial-gradient(30%_25%_at_72%_60%,rgba(58,166,255,.18),transparent_70%),radial-gradient(30%_25%_at_50%_80%,rgba(255,183,27,.10),transparent_70%)] opacity-90 blur-[40px]"></div>
                <div class="absolute bottom-[18%] left-1/2 h-0.5 w-[220px] -translate-x-1/2 overflow-hidden rounded-full bg-slate-900/10">
                    <span class="absolute inset-0 bg-[linear-gradient(90deg,transparent,#2b5cff,transparent)] bg-[length:220%_100%] bg-[position:200%_0] motion-safe:animate-[auth-logo-sweep_1400ms_ease-in-out_infinite]"></span>
                </div>
            </div>

            <div
                class="pointer-events-none fixed inset-0 z-50 grid place-items-center opacity-0 transition-opacity duration-[600ms] ease-[cubic-bezier(.4,0,.2,1)] data-[state=exit]:opacity-100 data-[state=on]:opacity-100 data-[state=sweep]:opacity-100"
                data-auth-logo-overlay
                data-state="off"
                aria-hidden="true"
            >
                <div
                    class="relative aspect-[1536/1024] w-[min(58vw,720px)] scale-[.96] opacity-0 drop-shadow-[0_24px_40px_rgba(20,28,60,.12)] drop-shadow-[0_6px_14px_rgba(20,28,60,.08)] transition-[transform,opacity] duration-[700ms] ease-[cubic-bezier(.4,0,.2,1)] data-[state=exit]:scale-[1.04] data-[state=exit]:opacity-0 data-[state=on]:scale-100 data-[state=on]:opacity-100 data-[state=sweep]:scale-100 data-[state=sweep]:opacity-100 max-sm:w-[82vw]"
                    data-auth-logo-box
                    data-state="off"
                >
                    <div class="absolute -inset-[14%] bg-[radial-gradient(closest-side,rgba(43,92,255,.18),transparent_70%),radial-gradient(closest-side_at_70%_60%,rgba(58,166,255,.12),transparent_75%)] opacity-100 blur-[30px]"></div>
                    <div class="absolute left-1/2 top-1/2 aspect-square w-[78%] -translate-x-1/2 -translate-y-1/2 rounded-full border border-[#2b5cff]/20"></div>
                    <img src="{{ asset('images/sofia-transparent.png') }}" alt="" class="absolute inset-0 h-full w-full object-contain">
                    <div
                        class="absolute inset-0 bg-[linear-gradient(105deg,rgba(255,255,255,0)_35%,rgba(220,232,255,.55)_46%,rgba(255,255,255,.95)_50%,rgba(220,232,255,.55)_54%,rgba(255,255,255,0)_65%)] bg-[length:220%_100%] bg-[position:200%_0] opacity-0 mix-blend-screen blur-[.6px] [-webkit-mask-image:url(/images/sofia-transparent.png)] [-webkit-mask-position:center] [-webkit-mask-repeat:no-repeat] [-webkit-mask-size:contain] [mask-image:url(/images/sofia-transparent.png)] [mask-position:center] [mask-repeat:no-repeat] [mask-size:contain] data-[active=true]:animate-[auth-logo-sweep_1500ms_cubic-bezier(.45,.05,.2,1)_forwards]"
                        data-auth-logo-sweep
                        data-active="false"
                    ></div>
                </div>
            </div>
        </div>

        <script src="{{ asset('assets/js/history-guard.js') }}?v=1"></script>
        <script>
            (() => {
                const initCinematicAuth = () => {
                    const root = document.querySelector('[data-auth-root]');

                    if (! root || root.dataset.authReady === '1') {
                        return;
                    }

                    root.dataset.authReady = '1';

                    const stage = root.querySelector('[data-auth-stage]');
                    const card = root.querySelector('[data-auth-card]');
                    const form = root.querySelector('[data-auth-login-form]');
                    const veil = root.querySelector('[data-auth-veil]');
                    const logoOverlay = root.querySelector('[data-auth-logo-overlay]');
                    const logoBox = root.querySelector('[data-auth-logo-box]');
                    const logoSweep = root.querySelector('[data-auth-logo-sweep]');
                    const timeNode = root.querySelector('[data-auth-time]');
                    const dateNode = root.querySelector('[data-auth-date]');
                    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
                    let timers = [];

                    const setTimer = (callback, delay) => {
                        const timer = window.setTimeout(callback, delay);
                        timers.push(timer);
                        return timer;
                    };

                    const clearTimers = () => {
                        timers.forEach((timer) => window.clearTimeout(timer));
                        timers = [];
                    };

                    const updateClock = () => {
                        const now = new Date();

                        if (timeNode) {
                            timeNode.textContent = now.toLocaleTimeString('es-CO', {
                                hour: '2-digit',
                                minute: '2-digit',
                            });
                        }

                        if (dateNode) {
                            dateNode.textContent = now.toLocaleDateString('es-CO', {
                                weekday: 'long',
                                day: '2-digit',
                                month: 'long',
                            });
                        }
                    };

                    updateClock();
                    window.setInterval(updateClock, 1000);

                    card?.addEventListener('pointermove', (event) => {
                        const rect = card.getBoundingClientRect();
                        card.style.setProperty('--auth-mx', `${event.clientX - rect.left}px`);
                        card.style.setProperty('--auth-my', `${event.clientY - rect.top}px`);
                    });

                    const password = form?.querySelector('[data-auth-password]');
                    const toggle = form?.querySelector('[data-auth-password-toggle]');
                    const eyeOpen = form?.querySelector('[data-eye-open]');
                    const eyeClosed = form?.querySelector('[data-eye-closed]');

                    toggle?.addEventListener('click', () => {
                        const isVisible = password.type === 'text';

                        password.type = isVisible ? 'password' : 'text';
                        toggle.setAttribute('aria-pressed', String(! isVisible));
                        toggle.setAttribute('aria-label', isVisible ? 'Mostrar contraseña' : 'Ocultar contraseña');
                        eyeOpen?.classList.toggle('hidden', ! isVisible);
                        eyeClosed?.classList.toggle('hidden', isVisible);
                    });

                    const setSubmitState = (state) => {
                        const submit = form?.querySelector('[data-auth-submit]');
                        const submitText = form?.querySelector('[data-auth-submit-text]');
                        const submitSpinner = form?.querySelector('[data-auth-submit-spinner]');
                        const submitIcon = form?.querySelector('[data-auth-submit-icon]');

                        if (! submit || ! submitText) {
                            return;
                        }

                        submit.dataset.state = state;
                        submit.disabled = state !== 'idle';
                        submitText.textContent = state === 'idle' ? 'Iniciar sesión' : 'Autenticando...';
                        submitSpinner?.classList.toggle('hidden', state === 'idle');
                        submitIcon?.classList.toggle('hidden', state !== 'idle');
                    };

                    const clearErrors = () => {
                        form?.querySelectorAll('[data-auth-error-for]').forEach((node) => {
                            node.textContent = '';
                            node.classList.add('hidden');
                        });

                        form?.querySelectorAll('[data-auth-field-shell]').forEach((node) => {
                            node.dataset.invalid = 'false';
                        });

                        form?.querySelectorAll('[aria-invalid="true"]').forEach((node) => {
                            node.setAttribute('aria-invalid', 'false');
                        });

                        const general = form?.querySelector('[data-auth-general-error]');

                        if (general) {
                            general.textContent = '';
                            general.classList.add('hidden');
                        }
                    };

                    const showErrors = (payload) => {
                        const errors = payload?.errors || {};
                        let displayed = false;

                        Object.entries(errors).forEach(([field, messages]) => {
                            const message = Array.isArray(messages) ? messages[0] : messages;
                            const errorNode = form?.querySelector(`[data-auth-error-for="${field}"]`);
                            const fieldNode = form?.querySelector(`[name="${field}"]`);
                            const shell = fieldNode?.closest('[data-auth-field-shell]');

                            if (errorNode && message) {
                                errorNode.textContent = message;
                                errorNode.classList.remove('hidden');
                                displayed = true;
                            }

                            shell?.setAttribute('data-invalid', 'true');
                            fieldNode?.setAttribute('aria-invalid', 'true');
                        });

                        if (! displayed) {
                            const general = form?.querySelector('[data-auth-general-error]');
                            const message = payload?.message || 'No pudimos iniciar sesión. Revisa tus datos e inténtalo de nuevo.';

                            if (general) {
                                general.textContent = message;
                                general.classList.remove('hidden');
                            }
                        }
                    };

                    const resetAnimation = () => {
                        clearTimers();
                        stage.dataset.phase = 'idle';
                        veil.dataset.active = 'false';
                        logoOverlay.dataset.state = 'off';
                        logoBox.dataset.state = 'off';
                        logoSweep.dataset.active = 'false';
                        setSubmitState('idle');
                    };

                    const setZoomOrigin = () => {
                        const target = root.querySelector('[data-auth-sofia-logo]');

                        if (! stage || ! target) {
                            return;
                        }

                        const stageRect = stage.getBoundingClientRect();
                        const targetRect = target.getBoundingClientRect();
                        const x = ((targetRect.left + targetRect.width / 2) - stageRect.left) / stageRect.width * 100;
                        const y = ((targetRect.top + targetRect.height / 2) - stageRect.top) / stageRect.height * 100;

                        stage.style.setProperty('--auth-zoom-x', `${x}%`);
                        stage.style.setProperty('--auth-zoom-y', `${y}%`);
                    };

                    const postLogin = async () => {
                        const response = await fetch(form.action, {
                            method: form.method || 'POST',
                            body: new FormData(form),
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const contentType = response.headers.get('content-type') || '';
                        const payload = contentType.includes('application/json') ? await response.json() : null;

                        if (response.ok) {
                            return {
                                redirected: response.redirected ? response.url : null,
                                twoFactor: Boolean(payload?.two_factor),
                            };
                        }

                        const error = new Error(payload?.message || 'Error de autenticación');
                        error.payload = payload;
                        error.status = response.status;
                        throw error;
                    };

                    form?.addEventListener('submit', async (event) => {
                        if (form.dataset.authNativeSubmit === '1' || form.dataset.authBusy === '1' || ! window.fetch) {
                            return;
                        }

                        event.preventDefault();
                        clearErrors();
                        clearTimers();
                        form.dataset.authBusy = '1';
                        setZoomOrigin();
                        setSubmitState('pressing');
                        stage.dataset.phase = 'idle';
                        veil.dataset.active = 'false';
                        logoOverlay.dataset.state = 'off';
                        logoBox.dataset.state = 'off';
                        logoSweep.dataset.active = 'false';

                        const startedAt = Date.now();
                        const reduce = reducedMotion.matches;

                        if (reduce) {
                            setTimer(() => {
                                stage.dataset.phase = 'zooming';
                                veil.dataset.active = 'true';
                            }, 120);
                        } else {
                            setTimer(() => {
                                setSubmitState('processing');
                                stage.dataset.phase = 'processing';
                            }, 160);
                            setTimer(() => {
                                stage.dataset.phase = 'zooming';
                                veil.dataset.active = 'true';
                            }, 700);
                            setTimer(() => {
                                logoOverlay.dataset.state = 'on';
                                logoBox.dataset.state = 'on';
                            }, 1700);
                            setTimer(() => {
                                logoOverlay.dataset.state = 'sweep';
                                logoBox.dataset.state = 'sweep';
                                logoSweep.dataset.active = 'true';
                            }, 2200);
                        }

                        try {
                            const result = await postLogin();
                            const destination = result.redirected
                                || (result.twoFactor ? root.dataset.authTwoFactorUrl : root.dataset.authHomeUrl);
                            const elapsed = Date.now() - startedAt;
                            const exitAt = reduce ? Math.max(260 - elapsed, 0) : Math.max(3800 - elapsed, 0);

                            setTimer(() => {
                                logoOverlay.dataset.state = 'exit';
                                logoBox.dataset.state = 'exit';
                            }, exitAt);
                            setTimer(() => {
                                window.location.assign(destination);
                            }, exitAt + (reduce ? 120 : 620));
                        } catch (error) {
                            form.dataset.authBusy = '0';
                            resetAnimation();

                            if (error.status === 419) {
                                showErrors({ message: 'La sesión expiró. Recarga la página e intenta de nuevo.' });
                                return;
                            }

                            showErrors(error.payload);
                        }
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initCinematicAuth);
                } else {
                    initCinematicAuth();
                }

                document.addEventListener('livewire:navigated', initCinematicAuth);
            })();
        </script>
        @fluxScripts
    </body>
</html>
