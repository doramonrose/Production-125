@php
    $user = auth()->user();
    $userName = $user?->name_th ?: $user?->name ?? __('User');
    $userAvatar = trim((string) ($user?->image_url ?? (session('staff_photo_url') ?? '')));
    $mainMenuUrl = url('/dashboard');
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName() ?? 'page';
    $pageTitles = [
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
    ];
    $pageTitle = $title ?? ($pageTitles[$routeName] ?? 'หน้าระบบ');
    $canChangePassword = \Illuminate\Support\Facades\Route::has('password.update');
    $canLogout = \Illuminate\Support\Facades\Route::has('logout');
@endphp
<nav class="animate-in slide-in-from-top-4 fade-in-0 duration-500 fill-mode-both relative z-50 flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all shadow-none duration-250 ease-soft-in rounded-2xl lg:flex-nowrap lg:justify-start"
    navbar-main navbar-scroll="true">
    <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
        <nav>
            <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
                <li class="leading-normal text-sm">
                    <a class="opacity-50 text-slate-700" href="{{ route('budget.manage.index') }}">หน้าหลัก</a>
                </li>
                <li class="text-sm pl-2 capitalize leading-normal text-slate-700 before:float-left before:pr-2 before:text-gray-600 before:content-['/']"
                    aria-current="page">
                    {{ $pageTitle }}
                </li>
            </ol>
        </nav>

        <div class="flex items-center mt-2 grow sm:mt-0 sm:mr-6 md:mr-0 lg:flex lg:basis-auto">
            <div class="flex items-center md:ml-auto md:pr-4"></div>
            <ul class="flex flex-row items-center justify-end pl-0 mb-0 list-none md-max:w-full">
                <li class="flex items-center">
                    <div class="block px-0 py-2 font-semibold transition-all ease-nav-brand text-sm text-slate-500">
                        @if ($userAvatar !== '')
                            <img src="{{ $userAvatar }}" alt="{{ $userName }}"
                                class="inline-block h-9 w-9 rounded-full object-cover shadow-md sm:mr-1"
                                referrerpolicy="no-referrer" />
                        @else
                            <i class="fa fa-user sm:mr-1"></i>
                        @endif
                        <span class="hidden sm:inline">{{ $userName }}</span>
                        <a href="{{ $mainMenuUrl }}"
                            class="group ml-2 inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white/90 px-2.5 py-2.5 text-xs font-semibold text-slate-700 shadow-soft-2xl transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white hover:shadow-md"
                            data-ui-tooltip="กลับเมนูหลัก" data-ui-tooltip-placement="bottom" aria-label="กลับเมนูหลัก">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors group-hover:bg-lime-100 group-hover:text-lime-700">
                                <i class="fas fa-house text-[10px]"></i>
                            </span>
                            เมนูหลัก
                        </a>
                    </div>
                </li>

                <li class="flex items-center pl-4 xl:hidden">
                    <a href="javascript:;" class="block p-0 transition-all ease-nav-brand text-sm text-slate-500"
                        sidenav-trigger>
                        <div class="w-4.5 overflow-hidden">
                            <i
                                class="ease-soft mb-0.75 relative block h-0.5 rounded-sm bg-slate-500 transition-all"></i>
                            <i
                                class="ease-soft mb-0.75 relative block h-0.5 rounded-sm bg-slate-500 transition-all"></i>
                            <i class="ease-soft relative block h-0.5 rounded-sm bg-slate-500 transition-all"></i>
                        </div>
                    </a>
                </li>

                @if ($canLogout)
                    <li class="flex items-center pl-4">
                        <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-logout' }))"
                            class="p-0 transition-all text-sm ease-nav-brand text-slate-500 hover:text-slate-700"
                            data-ui-tooltip="ออกจากระบบ" data-ui-tooltip-placement="bottom" aria-label="ออกจากระบบ">
                            <i class="fas fa-right-from-bracket"></i>
                        </button>
                    </li>
                @endif

                @if ($canChangePassword)
                    <li class="flex items-center px-4">
                        <button type="button" id="btn-open-change-password"
                            onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'change-password' }))"
                            class="p-0 transition-all text-sm ease-nav-brand text-slate-500 hover:text-slate-700"
                            data-ui-tooltip="แก้ไขรหัสผ่าน" data-ui-tooltip-placement="bottom" aria-label="แก้ไขรหัสผ่าน">
                            <i class="cursor-pointer fa fa-cog"></i>
                        </button>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>

@includeWhen($canChangePassword, 'layouts.partials.modal-change-password')

@if ($canLogout)
    <x-confirm-modal name="confirm-logout" :action="route('logout')" title="ยืนยันออกจากระบบ?"
        message="คุณต้องการออกจากระบบใช่หรือไม่" confirmText="ออกจากระบบ" confirmVariant="danger"
        icon="fas fa-right-from-bracket" />
@endif
