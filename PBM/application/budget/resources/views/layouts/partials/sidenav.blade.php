@php
    $settingsMenus = collect(config('access_control.menus', []))
        ->filter(fn ($menu) => str_starts_with($menu['key'], 'budget-settings-'))
        ->values();
    $canAccess = function (string $menuKey): bool {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'canAccessMenu')) {
            return true;
        }

        return $user->canAccessMenu($menuKey);
    };
    $visibleSettingsMenus = $settingsMenus->filter(fn ($menu) => $canAccess($menu['key']));
    $settingsSectionOpen = request()->routeIs('budget.settings.*');
@endphp
<!-- sidenav -->
<aside class="sidenav-aside max-w-62.5 ease-nav-brand z-990 fixed inset-y-0 left-0 my-4 ml-4 flex h-[calc(100vh-2rem)] w-full -translate-x-[120%] flex-col overflow-hidden rounded-2xl border-0 bg-white p-0 antialiased shadow-none transition-transform duration-200 xl:my-0 xl:ml-0 xl:h-screen xl:translate-x-0 xl:rounded-none xl:bg-transparent motion-reduce:animate-none animate-in slide-in-from-left-6 fade-in-0 duration-500 fill-mode-both">
    <div class="h-19.5 shrink-0">
        <i class="absolute top-0 right-0 hidden p-4 opacity-50 cursor-pointer fas fa-times text-slate-400 xl:hidden" sidenav-close></i>
        <a class="block px-8 py-6 m-0 text-sm whitespace-nowrap text-slate-700" href="{{ route('budget.manage.index') }}">
            <img src="{{ asset('images/logo_phar_cmu_thai.png') }}" class="inline h-full max-w-full transition-all duration-200 ease-nav-brand max-h-8" alt="main_logo" />
            <span class="ml-1 font-semibold transition-all duration-200 ease-nav-brand">ระบบงบประมาณ</span>
        </a>
    </div>

    <hr class="h-px shrink-0 mt-0 bg-transparent bg-gradient-to-r from-transparent via-black/40 to-transparent" />

    {{-- ใช้ [overflow-y:auto] แทน overflow-y-auto เพื่อไม่ให้ soft-ui perfect-scrollbar.js ผูก PerfectScrollbar แล้วทำให้เลื่อนไม่ได้ --}}
    <div data-sidenav-scroll class="min-h-0 flex-1 touch-pan-y overscroll-y-contain pb-6 [overflow-y:auto] [-webkit-overflow-scrolling:touch]">
        <ul class="flex flex-col pl-0 mb-0">
            @if ($canAccess('dashboard'))
                <li class="mt-0.5 w-full">
                    <a class="py-2.7 {{ request()->routeIs('dashboard') ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors" @if (request()->routeIs('dashboard')) data-sidenav-active @endif href="{{ route('dashboard') }}">
                        <div class="{{ request()->routeIs('dashboard') ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 items-center justify-center rounded-lg text-center xl:p-2.5">
                            <i class="fas fa-house text-sm {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-600' }}"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Dashboard</span>
                    </a>
                </li>
            @endif

            @if ($canAccess('budget-manage'))
                <li class="mt-0.5 w-full">
                    <a class="py-2.7 {{ request()->routeIs('budget.manage.*') ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors" @if (request()->routeIs('budget.manage.*')) data-sidenav-active @endif href="{{ route('budget.manage.index') }}">
                        <div class="{{ request()->routeIs('budget.manage.*') ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 items-center justify-center rounded-lg text-center xl:p-2.5">
                            <i class="fas fa-coins text-sm {{ request()->routeIs('budget.manage.*') ? 'text-white' : 'text-slate-600' }}"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">บริหารงบประมาณ</span>
                    </a>
                </li>
            @endif

            <li class="mt-0.5 w-full">
                <a class="py-2.7 {{ request()->routeIs('budget.one-planning.*') ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm my-0 mx-4 flex items-center rounded-lg px-4" @if (request()->routeIs('budget.one-planning.*')) data-sidenav-active @endif href="{{ route('budget.one-planning.index') }}">
                    <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow-soft-2xl"><i class="fas fa-cloud-arrow-down text-slate-600"></i></div>
                    <span>งบประมาณ One Planing</span>
                </a>
            </li>

            @if ($visibleSettingsMenus->isNotEmpty())
                <li class="mt-3 w-full px-3">
                    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-slate-100/90 shadow-sm">
                        <details
                            class="group"
                            @if ($settingsSectionOpen) open @endif
                            ontoggle="(function(d){var c=d.querySelector('[data-settings-chevron]');if(c)c.classList.toggle('rotate-180',d.open);})(this)"
                        >
                            <summary
                                class="flex cursor-pointer list-none items-center gap-2 px-3 py-2.5 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-200/50 [&::-webkit-details-marker]:hidden"
                            >
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-center text-slate-600 shadow-sm">
                                    <i class="fas fa-screwdriver-wrench text-sm"></i>
                                </span>
                                <span class="min-w-0 flex-1 leading-snug">ตั้งค่าข้อมูลพื้นฐาน</span>
                                <i
                                    data-settings-chevron
                                    class="fas fa-chevron-down ml-1 shrink-0 text-xs text-slate-500 transition-transform duration-200 {{ $settingsSectionOpen ? 'rotate-180' : '' }}"
                                ></i>
                            </summary>
                            <ul class="mb-1.5 mt-0 border-t border-slate-200/60 bg-slate-100/50 pb-1.5 pt-1">
                                @foreach ($visibleSettingsMenus as $menu)
                                    @php
                                        $active = request()->routeIs($menu['route']);
                                    @endphp
                                    <li class="mt-0.5 w-full">
                                        <a
                                            class="py-2.7 {{ $active ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors"
                                            @if ($active) data-sidenav-active @endif
                                            href="{{ route($menu['route']) }}"
                                        >
                                            <div class="{{ $active ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-center xl:p-2.5">
                                                <i class="{{ $menu['icon'] }} text-sm {{ $active ? 'text-white' : 'text-slate-600' }}"></i>
                                            </div>
                                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">{{ $menu['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    </div>
                </li>
            @endif
        </ul>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const active = document.querySelector('.sidenav-aside [data-sidenav-active]');
            const scroller = document.querySelector('.sidenav-aside [data-sidenav-scroll]');
            if (!active || !scroller) return;

            const alignActiveMenu = () => {
                const top = active.getBoundingClientRect().top - scroller.getBoundingClientRect().top;
                const next = scroller.scrollTop + top - (scroller.clientHeight / 2) + (active.offsetHeight / 2);
                scroller.scrollTop = Math.max(0, next);
            };

            alignActiveMenu();
            requestAnimationFrame(alignActiveMenu);
        });
    </script>
</aside>
<!-- end sidenav -->
