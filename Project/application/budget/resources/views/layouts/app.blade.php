@php
    $softUiPath = config('ui.soft_ui.path', 'soft-ui');
    $softUiVersion = config('ui.soft_ui.version', '1.0.5');
    $routeName = request()->route()?->getName();
    $pageTitle = $title
        ?? match ($routeName) {
            'dashboard' => 'Dashboard',
            'budget.one-planning.index' => 'งบประมาณ One Planing',
            'budget.manage.index' => 'บริหารงบประมาณ',
            'budget.manage.show' => 'รายละเอียดงบประมาณ',
            'budget.settings.year.index' => 'ตั้งค่าปีงบประมาณ',
            'budget.settings.plan.index' => 'ตั้งค่าแผน',
            'budget.settings.event.index' => 'ตั้งค่างาน-กิจกรรม',
            'budget.settings.activity.index' => 'ตั้งค่างาน-กิจกรรม',
            'budget.settings.fund.index' => 'ตั้งค่ากองทุน',
            'budget.settings.cost.index' => 'ตั้งค่าต้นทุน',
            'budget.settings.source.index' => 'ตั้งค่าแหล่งงบ',
            'budget.settings.type.index' => 'ตั้งค่าประเภทงบ',
            'budget.settings.category-budget.index' => 'ตั้งค่าหมวดงบ',
            'budget.settings.category-pay.index' => 'ตั้งค่าหมวดรายจ่าย',
            'budget.settings.pay-list.index' => 'ตั้งค่ารายการจ่าย',
            'budget.settings.expenses.index' => 'ตั้งค่ารายจ่ายย่อย',
            default => $routeName ? \Illuminate\Support\Str::headline(str_replace('.', ' ', $routeName)) : 'ระบบงบประมาณ',
        };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('images/logo_phar_cmu_thai.png') }}" />
    <link rel="icon" type="image/png" href="{{ asset('images/logo_phar_cmu_thai.png') }}" />

    <title>{{ config('app.name', 'ระบบงบประมาณ') }} - {{ $pageTitle }}</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="{{ asset("{$softUiPath}/assets/css/nucleo-icons.css") }}" rel="stylesheet" />
    <link href="{{ asset("{$softUiPath}/assets/css/nucleo-svg.css") }}" rel="stylesheet" />
    <link href="{{ asset("{$softUiPath}/assets/css/soft-ui-dashboard-tailwind.css") }}?v={{ $softUiVersion }}"
        rel="stylesheet" />
    <link href="{{ asset("{$softUiPath}/assets/css/perfect-scrollbar.css") }}" rel="stylesheet" />

    @include('layouts.partials.cmu-fonts')
    @stack('styles')
</head>

<body class="m-0 min-h-screen font-sans antialiased font-normal text-base leading-default bg-gray-50 text-slate-500">
    <div id="page-loader"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-50/40 backdrop-blur-md transition-opacity duration-500 opacity-100 pointer-events-auto"
        aria-hidden="true">
        <div class="flex flex-col items-center gap-8 px-10 py-8">
            <div class="relative h-28 w-28">
                <div
                    class="absolute inset-0 rounded-full border-[3px] border-transparent border-t-emerald-600 border-r-green-600 motion-reduce:animate-none animate-spin [animation-duration:800ms]">
                </div>
                <div
                    class="absolute inset-1.5 rounded-full border-[3px] border-transparent border-b-green-400 border-l-green-300 motion-reduce:animate-none animate-spin [animation-duration:1200ms] [animation-direction:reverse]">
                </div>

                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="relative h-20 w-20">
                        <div
                            class="absolute inset-0 -m-4 rounded-full border-2 border-blue-600/15 border-t-blue-600/80 border-r-emerald-500/80 motion-reduce:animate-none animate-spin [animation-duration:900ms]">
                        </div>
                        <div
                            class="absolute inset-0 -m-6 rounded-full border border-slate-500/10 border-t-slate-500/40 motion-reduce:animate-none animate-spin [animation-duration:1600ms] [animation-direction:reverse]">
                        </div>
                        <img src="{{ asset('images/logo_phar_cmu_thai.png') }}" alt=""
                            class="relative z-10 h-20 w-20 object-contain opacity-95" />
                    </div>
                </div>
            </div>

            <p class="text-[0.85rem] font-medium tracking-widest text-slate-500">
                <span class="inline-block motion-reduce:animate-none animate-bounce">ก</span>
                <span class="inline-block motion-reduce:animate-none animate-bounce delay-75">ำ</span>
                <span class="inline-block motion-reduce:animate-none animate-bounce delay-150">ลัง</span>
                <span class="inline-block motion-reduce:animate-none animate-bounce delay-200">โ</span>
                <span class="inline-block motion-reduce:animate-none animate-bounce delay-300">ห</span>
                <span class="inline-block motion-reduce:animate-none animate-bounce delay-400">ล</span>
                <span class="inline-block motion-reduce:animate-none animate-bounce delay-500">ด</span>
                <span class="inline-block motion-reduce:animate-none animate-bounce delay-700">.</span>
                <span class="inline-block motion-reduce:animate-none animate-bounce delay-800">.</span>
                <span class="inline-block motion-reduce:animate-none animate-bounce delay-900">.</span>
            </p>
        </div>
    </div>

    @include('layouts.partials.sidenav')

    <main class="ease-soft-in-out xl:ml-68.5 relative min-h-screen rounded-xl transition-all duration-200 bg-gray-50">
        @include('layouts.partials.navbar')

        <div class="w-full px-4 mx-auto animate-in slide-in-from-bottom-8 fade-in-0 duration-700 fill-mode-both">
            @isset($header)
                {{ $header }}
            @endisset

            {{ $slot }}

            @include('layouts.partials.footer')
        </div>
    </main>

    <script>
        (function() {
            var loader = document.getElementById('page-loader');
            var minShowTime = 100;
            var startTime = Date.now();

            function hideLoader() {
                var elapsed = Date.now() - startTime;
                var delay = Math.max(0, minShowTime - elapsed);
                setTimeout(function() {
                    if (loader) {
                        loader.classList.add('opacity-0', 'pointer-events-none');
                        setTimeout(function() {
                            loader.classList.add('hidden');
                        }, 500);
                    }
                }, delay);
            }

            if (document.readyState === 'complete') hideLoader();
            else window.addEventListener('load', hideLoader);
        })();
    </script>
    <script>
        window.SOFT_UI_BASE = "{{ rtrim(asset($softUiPath), '/') }}/";
    </script>

    <script src="https://unpkg.com/@popperjs/core@2" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="{{ asset("{$softUiPath}/assets/js/plugins/chartjs.min.js") }}" defer></script>
    <script src="{{ asset("{$softUiPath}/assets/js/plugins/perfect-scrollbar.min.js") }}" defer></script>
    <script src="{{ asset("{$softUiPath}/assets/js/soft-ui-dashboard-tailwind.js") }}?v={{ $softUiVersion }}" defer>
    </script>

    <x-toast :flash="session('toast')" />
    <x-ui.tooltip-provider />
    @stack('scripts')
</body>

</html>
