@php
    $canMenu = function (string $menuKey): bool {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'canAccessMenu')) {
            return true;
        }

        return $user->canAccessMenu($menuKey);
    };
    $fmtMoney = static function ($n): string {
        $n = (float) $n;
        $decimals = abs($n - round($n)) < 0.0000001 ? 0 : 2;

        return number_format($n, $decimals, '.', ',');
    };
@endphp

<x-layouts.app title="Dashboard">
    <div class="py-2">
        <div
            class="mx-2 mb-4 overflow-hidden rounded-[28px] bg-gradient-to-br from-emerald-500/[0.12] via-blue-500/[0.10] via-[40%] to-white/60 shadow-[0_8px_32px_-8px_rgba(15,23,42,0.22),inset_0_1px_0_rgba(255,255,255,0.5)] backdrop-blur-[22px] backdrop-saturate-[1.7]">
            <div class="relative px-5 py-3 sm:px-6 sm:py-4 lg:px-8 lg:py-5">
                <div class="pointer-events-none absolute inset-0">
                    <div
                        class="absolute -top-16 -left-16 h-56 w-56 rounded-full opacity-55 bg-[radial-gradient(circle,rgba(16,185,129,0.40),transparent_65%)] blur-[44px]">
                    </div>
                    <div
                        class="absolute -bottom-20 -right-16 h-52 w-52 rounded-full opacity-50 bg-[radial-gradient(circle,rgba(59,130,246,0.38),transparent_65%)] blur-[44px]">
                    </div>
                    <div
                        class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/[0.85] to-transparent">
                    </div>
                </div>
                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex h-9 w-9 items-center justify-center rounded-2xl !bg-[linear-gradient(135deg,#059669,#2563eb)] text-white shadow-lg shadow-emerald-500/25 ring-1 ring-white/20">
                                <i class="fas fa-house text-[13px] drop-shadow-[0_1px_2px_rgba(0,0,0,0.25)]"></i>
                            </span>
                            <h2 class="truncate text-[22px] font-black tracking-tight text-slate-950 lg:text-[28px]">
                                Dashboard
                            </h2>
                        </div>
                        <p class="mt-1 max-w-[58ch] text-xs leading-relaxed text-slate-600 lg:text-sm">
                            ภาพรวมงบประมาณและการเบิกจ่ายทั้งระบบ สำหรับผู้บริหารติดตามสถานะการใช้จ่ายได้ทันที
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 lg:shrink-0 lg:justify-end">
                        @if ($canMenu('budget-manage'))
                            <a href="{{ route('budget.manage.index') }}"
                                class="inline-flex h-10 items-center justify-center gap-2 rounded-2xl border border-emerald-600/25 bg-white/80 px-4 text-sm font-bold text-emerald-800 shadow-sm ring-1 ring-white/40 transition hover:-translate-y-0.5 hover:bg-white">
                                <i class="fas fa-coins text-emerald-600"></i>
                                บริหารงบประมาณ
                            </a>
                        @endif
                        @if ($canMenu('budget-settings-year'))
                            <a href="{{ route('budget.settings.year.index') }}"
                                class="inline-flex h-10 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white/80 px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white">
                                <i class="fas fa-sliders-h text-slate-500"></i>
                                ตั้งค่าระบบ
                            </a>
                        @endif
                        <label for="dashboard-year-filter" class="sr-only">ปีงบประมาณ</label>
                        <div class="relative inline-flex">
                            <select id="dashboard-year-filter"
                                class="dashboard-year-select h-10 cursor-pointer rounded-2xl border border-slate-200 bg-white/80 py-2 pl-3.5 pr-10 text-sm font-semibold text-slate-700 shadow-sm outline-none transition hover:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                onchange="window.location.href=this.value">
                                <option value="{{ route('dashboard', ['year' => 'all']) }}"
                                    @selected($selectedYear === '')>
                                    ปีงบทั้งหมด
                                </option>
                                @foreach ($yearOptions as $year)
                                    <option value="{{ route('dashboard', ['year' => $year]) }}"
                                        @selected($selectedYear === $year)>
                                        ปีงบ {{ $year }}{{ $year === $currentYear ? ' (ปัจจุบัน)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <span
                                class="pointer-events-none absolute inset-y-0 right-0 flex w-9 items-center justify-center text-slate-400">
                                <i class="fas fa-chevron-down text-[10px]"></i>
                            </span>
                        </div>
                        <style>
                            select.dashboard-year-select {
                                -webkit-appearance: none !important;
                                -moz-appearance: none !important;
                                appearance: none !important;
                                background-image: none !important;
                            }

                            select.dashboard-year-select::-ms-expand {
                                display: none;
                            }
                        </style>
                    </div>
                </div>
            </div>
        </div>

        {{-- Executive KPIs --}}
        <div class="mx-2 mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div
                class="rounded-3xl border border-blue-200/70 bg-gradient-to-br from-blue-50/90 to-white p-4 shadow-soft-xl">
                <p class="text-[11px] font-bold uppercase tracking-wide text-blue-600/80">ยอดงบปัจจุบัน</p>
                <p class="mt-1 text-xl font-black tabular-nums text-blue-700 sm:text-2xl">{{ $fmtMoney($kpis['balance']) }}</p>
                <p class="mt-1 text-xs text-slate-500">บาท · {{ number_format($stats['manage_count']) }} รายการ</p>
            </div>
            <div
                class="rounded-3xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50/90 to-white p-4 shadow-soft-xl">
                <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-600/80">เบิกจ่ายแล้ว</p>
                <p class="mt-1 text-xl font-black tabular-nums text-emerald-700 sm:text-2xl">{{ $fmtMoney($kpis['withdraw']) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $kpis['pct_of_balance'] }}% ของยอดปัจจุบัน · {{ $kpis['pct_of_amount'] }}% ของงบตั้งต้น</p>
            </div>
            <div
                class="rounded-3xl border border-amber-200/70 bg-gradient-to-br from-amber-50/90 to-white p-4 shadow-soft-xl">
                <p class="text-[11px] font-bold uppercase tracking-wide text-amber-600/80">รอเบิกจ่าย</p>
                <p class="mt-1 text-xl font-black tabular-nums text-amber-700 sm:text-2xl">{{ $fmtMoney($kpis['wait']) }}</p>
                <p class="mt-1 text-xs text-slate-500">อยู่ในขั้นตอนอนุมัติ</p>
            </div>
            <div
                class="rounded-3xl border border-slate-200/80 bg-gradient-to-br from-slate-50 to-white p-4 shadow-soft-xl">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">คงเหลือหลังเบิก</p>
                <p class="mt-1 text-xl font-black tabular-nums text-slate-800 sm:text-2xl">{{ $fmtMoney($kpis['remain']) }}</p>
                <p class="mt-1 text-xs text-slate-500">จัดแผนแล้ว {{ $fmtMoney($kpis['plan_used']) }} ฿</p>
            </div>
        </div>

        {{-- Source donuts (existing) --}}
        <div class="mx-2 mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                class="group relative overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-b from-white to-slate-50/90 p-4 text-center shadow-[0_12px_40px_-18px_rgba(15,23,42,0.18)] ring-1 ring-white/80 transition-shadow duration-300 hover:shadow-[0_16px_48px_-16px_rgba(37,99,235,0.22)]">
                <div
                    class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-blue-500/[0.07] blur-2xl transition-opacity group-hover:opacity-100">
                </div>
                <h6 class="text-sm font-bold text-slate-600">งบประมาณรวม</h6>
                <h5 class="text-xl font-bold text-blue-600 mt-1">{{ $fmtMoney($summary['total']['balance']) }} ฿</h5>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">{{ $fmtMoney($summary['total']['remain']) }} คงเหลือ</p>
                <div
                    class="relative mx-auto mt-3 flex h-48 w-48 items-center justify-center rounded-full bg-gradient-to-br from-slate-100/90 to-white p-1 shadow-[inset_0_1px_0_rgba(255,255,255,0.9),0_4px_24px_-8px_rgba(37,99,235,0.25)] ring-1 ring-slate-200/60">
                    <canvas id="chartTotal" class="max-h-full max-w-full"></canvas>
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ $fmtMoney($summary['total']['wait']) }} รอเบิกจ่าย</p>
            </div>
            <div
                class="group relative overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-b from-white to-slate-50/90 p-4 text-center shadow-[0_12px_40px_-18px_rgba(15,23,42,0.18)] ring-1 ring-white/80 transition-shadow duration-300 hover:shadow-[0_16px_48px_-16px_rgba(22,163,74,0.2)]">
                <div
                    class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-emerald-500/[0.08] blur-2xl">
                </div>
                <h6 class="text-sm font-bold text-slate-600">เงินรายได้</h6>
                <h5 class="text-xl font-bold text-emerald-600 mt-1">{{ $fmtMoney($summary['income']['balance']) }} ฿</h5>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">{{ $fmtMoney($summary['income']['remain']) }} คงเหลือ</p>
                <div
                    class="relative mx-auto mt-3 flex h-48 w-48 items-center justify-center rounded-full bg-gradient-to-br from-slate-100/90 to-white p-1 shadow-[inset_0_1px_0_rgba(255,255,255,0.9),0_4px_24px_-8px_rgba(22,163,74,0.22)] ring-1 ring-slate-200/60">
                    <canvas id="chartIncome" class="max-h-full max-w-full"></canvas>
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ $fmtMoney($summary['income']['wait']) }} รอเบิกจ่าย</p>
            </div>
            <div
                class="group relative overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-b from-white to-slate-50/90 p-4 text-center shadow-[0_12px_40px_-18px_rgba(15,23,42,0.18)] ring-1 ring-white/80 transition-shadow duration-300 hover:shadow-[0_16px_48px_-16px_rgba(202,138,4,0.22)]">
                <div
                    class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-400/[0.12] blur-2xl">
                </div>
                <h6 class="text-sm font-bold text-slate-600">เงินแผ่นดิน</h6>
                <h5 class="text-xl font-bold text-amber-600 mt-1">{{ $fmtMoney($summary['gov']['balance']) }} ฿</h5>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">{{ $fmtMoney($summary['gov']['remain']) }} คงเหลือ</p>
                <div
                    class="relative mx-auto mt-3 flex h-48 w-48 items-center justify-center rounded-full bg-gradient-to-br from-slate-100/90 to-white p-1 shadow-[inset_0_1px_0_rgba(255,255,255,0.9),0_4px_24px_-8px_rgba(202,138,4,0.2)] ring-1 ring-slate-200/60">
                    <canvas id="chartGov" class="max-h-full max-w-full"></canvas>
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ $fmtMoney($summary['gov']['wait']) }} รอเบิกจ่าย</p>
            </div>
            <div
                class="group relative overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-b from-white to-slate-50/90 p-4 text-center shadow-[0_12px_40px_-18px_rgba(15,23,42,0.18)] ring-1 ring-white/80 transition-shadow duration-300 hover:shadow-[0_16px_48px_-16px_rgba(225,29,72,0.18)]">
                <div
                    class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-rose-500/[0.08] blur-2xl">
                </div>
                <h6 class="text-sm font-bold text-slate-600">เงินนอกงบประมาณ</h6>
                <h5 class="text-xl font-bold text-rose-600 mt-1">{{ $fmtMoney($summary['external']['balance']) }} ฿</h5>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">{{ $fmtMoney($summary['external']['remain']) }} คงเหลือ</p>
                <div
                    class="relative mx-auto mt-3 flex h-48 w-48 items-center justify-center rounded-full bg-gradient-to-br from-slate-100/90 to-white p-1 shadow-[inset_0_1px_0_rgba(255,255,255,0.9),0_4px_24px_-8px_rgba(225,29,72,0.2)] ring-1 ring-slate-200/60">
                    <canvas id="chartExternal" class="max-h-full max-w-full"></canvas>
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ $fmtMoney($summary['external']['wait']) }} รอเบิกจ่าย</p>
            </div>
        </div>

        {{-- Executive analytics --}}
        <div class="mx-2 mb-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-5 shadow-soft-xl backdrop-blur-sm">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">เปรียบเทียบงบ vs เบิกจ่าย ตามแหล่ง</h3>
                        <p class="mt-0.5 text-xs text-slate-500">ยอดปัจจุบัน · เบิกแล้ว · รอเบิก · คงเหลือ</p>
                    </div>
                </div>
                <div class="relative h-72">
                    <canvas id="chartSourceCompare"></canvas>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-5 shadow-soft-xl backdrop-blur-sm">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">สัดส่วนสถานะเงินรวม</h3>
                        <p class="mt-0.5 text-xs text-slate-500">เบิกจ่ายแล้ว / รอเบิกจ่าย / คงเหลือหลังเบิก</p>
                    </div>
                </div>
                <div class="relative mx-auto h-72 max-w-md">
                    <canvas id="chartStatusMix"></canvas>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-5 shadow-soft-xl backdrop-blur-sm">
                <div class="mb-3">
                    <h3 class="text-sm font-bold text-slate-800">งบประมาณและการเบิกจ่ายตามปี</h3>
                    <p class="mt-0.5 text-xs text-slate-500">เปรียบเทียบยอดงบ เบิกจ่าย และคงเหลือรายปีงบประมาณ</p>
                </div>
                <div class="relative h-72">
                    <canvas id="chartByYear"></canvas>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-5 shadow-soft-xl backdrop-blur-sm">
                <div class="mb-3">
                    <h3 class="text-sm font-bold text-slate-800">แนวโน้มการเบิกจ่ายรายเดือน</h3>
                    <p class="mt-0.5 text-xs text-slate-500">12 เดือนล่าสุด จากระบบเบิกจ่าย (อนุมัติแล้ว / รออนุมัติ)</p>
                </div>
                <div class="relative h-72">
                    <canvas id="chartMonthly"></canvas>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-5 shadow-soft-xl backdrop-blur-sm">
                <div class="mb-3">
                    <h3 class="text-sm font-bold text-slate-800">Top หมวดงบประมาณ</h3>
                    <p class="mt-0.5 text-xs text-slate-500">8 หมวดที่มีงบสูงสุด พร้อมยอดเบิกจ่ายและจัดแผนแล้ว</p>
                </div>
                <div class="relative h-80">
                    <canvas id="chartByCategory"></canvas>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-5 shadow-soft-xl backdrop-blur-sm">
                <div class="mb-3">
                    <h3 class="text-sm font-bold text-slate-800">Top แผนงาน</h3>
                    <p class="mt-0.5 text-xs text-slate-500">8 แผนที่มีงบสูงสุด เทียบงบปัจจุบันกับเบิกจ่าย</p>
                </div>
                <div class="relative h-80">
                    <canvas id="chartByPlan"></canvas>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-5 shadow-soft-xl backdrop-blur-sm xl:col-span-2">
                <div class="mb-3">
                    <h3 class="text-sm font-bold text-slate-800">แผนการใช้จ่าย vs การเบิกจ่ายจริง (ตามกองทุน)</h3>
                    <p class="mt-0.5 text-xs text-slate-500">ดูว่ากองทุนใดจัดแผนไปแล้วมาก และเบิกจ่ายจริงตามมาหรือยัง</p>
                </div>
                <div class="relative h-80">
                    <canvas id="chartByFund"></canvas>
                </div>
            </div>
        </div>

        <div class="mx-2">
            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-5 shadow-soft-xl backdrop-blur-sm">
                <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">สรุปสถานะรายการงบประมาณ</h3>
                        <p class="mt-0.5 text-xs text-slate-500">ภาพรวมจำนวนรายการที่ผู้บริหารควรติดตาม</p>
                    </div>
                    <p class="text-xs font-semibold text-slate-500">
                        ทั้งหมด <span class="text-slate-900">{{ number_format($stats['manage_count']) }}</span> รายการ
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-3">
                        <p class="text-[11px] font-bold text-emerald-700/80">มีการเบิกจ่ายแล้ว</p>
                        <p class="mt-1 text-2xl font-black tabular-nums text-emerald-700">{{ number_format($stats['with_withdraw']) }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-500">{{ $stats['disburse_coverage_pct'] }}% ของทั้งหมด</p>
                    </div>
                    <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-3">
                        <p class="text-[11px] font-bold text-amber-700/80">มีรายการรอเบิก</p>
                        <p class="mt-1 text-2xl font-black tabular-nums text-amber-700">{{ number_format($stats['with_wait']) }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-500">อยู่ในขั้นตอนอนุมัติ</p>
                    </div>
                    <div class="rounded-2xl border border-violet-100 bg-violet-50/70 p-3">
                        <p class="text-[11px] font-bold text-violet-700/80">จัดแผนการใช้แล้ว</p>
                        <p class="mt-1 text-2xl font-black tabular-nums text-violet-700">{{ number_format($stats['with_plan']) }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-500">{{ $stats['plan_coverage_pct'] }}% ของทั้งหมด</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                        <p class="text-[11px] font-bold text-slate-600">ยังไม่มีการเคลื่อนไหว</p>
                        <p class="mt-1 text-2xl font-black tabular-nums text-slate-700">{{ number_format($stats['no_activity']) }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-500">ยังไม่จัดแผน/เบิก/รอเบิก</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-3">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">แยกตามแหล่งงบ</p>
                        <ul class="space-y-1.5 text-sm text-slate-700">
                            <li class="flex items-center justify-between gap-3">
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>เงินรายได้
                                </span>
                                <span class="font-semibold tabular-nums">{{ number_format($stats['by_source']['income']) }}</span>
                            </li>
                            <li class="flex items-center justify-between gap-3">
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>เงินแผ่นดิน
                                </span>
                                <span class="font-semibold tabular-nums">{{ number_format($stats['by_source']['gov']) }}</span>
                            </li>
                            <li class="flex items-center justify-between gap-3">
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-rose-500"></span>เงินนอกงบประมาณ
                                </span>
                                <span class="font-semibold tabular-nums">{{ number_format($stats['by_source']['external']) }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-3">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">เอกสารเบิกจ่ายที่เชื่อมงบ</p>
                        <ul class="space-y-1.5 text-sm text-slate-700">
                            <li class="flex items-center justify-between gap-3">
                                <span>อนุมัติแล้ว</span>
                                <span class="font-semibold tabular-nums text-emerald-700">{{ number_format($stats['withdraw_docs_approved']) }}</span>
                            </li>
                            <li class="flex items-center justify-between gap-3">
                                <span>รออนุมัติ</span>
                                <span class="font-semibold tabular-nums text-amber-700">{{ number_format($stats['withdraw_docs_pending']) }}</span>
                            </li>
                            <li class="flex items-center justify-between gap-3">
                                <span>รวมเอกสาร</span>
                                <span class="font-semibold tabular-nums">{{ number_format($stats['withdraw_docs_total']) }}</span>
                            </li>
                            <li class="flex items-center justify-between gap-3 border-t border-slate-200/80 pt-1.5">
                                <span>เบิกครบยอดงบแล้ว</span>
                                <span class="font-semibold tabular-nums">{{ number_format($stats['fully_disbursed']) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                const chartPercents = @json($chart);
                const summary = @json($summary);
                const chartsData = @json($charts);
                const charts = {};
                const fontStack = '"K2D", sans-serif';
                const money = (v) => {
                    const n = Number(v || 0);
                    const isWhole = Number.isFinite(n) && Math.abs(n - Math.round(n)) < 1e-9;
                    return n.toLocaleString(undefined, {
                        minimumFractionDigits: isWhole ? 0 : 2,
                        maximumFractionDigits: 2
                    });
                };
                const moneyShort = (v) => {
                    const n = Number(v || 0);
                    const abs = Math.abs(n);
                    if (abs >= 1e6) return (n / 1e6).toFixed(abs >= 1e7 ? 1 : 2) + 'ล.';
                    if (abs >= 1e3) return (n / 1e3).toFixed(abs >= 1e4 ? 0 : 1) + 'พ.';
                    return money(n);
                };

                const centerTextPlugin = {
                    id: 'dashboardDonutCenter',
                    afterDraw(chart) {
                        const {
                            ctx,
                            chartArea
                        } = chart;
                        if (!chartArea || chartArea.width <= 0) return;
                        const cx = chartArea.left + chartArea.width / 2;
                        const cy = chartArea.top + chartArea.height / 2;
                        const percent = chart.config.options.plugins?.custom?.percent ?? 0;
                        const amount = chart.config.options.plugins?.custom?.amount ?? 0;
                        const accent = chart.config.options.plugins?.custom?.accent ?? '#64748b';
                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.fillStyle = '#94a3b8';
                        ctx.font = `400 10px ${fontStack}`;
                        ctx.fillText('เบิกจ่าย', cx, cy - 16);
                        ctx.fillStyle = accent;
                        ctx.font = `700 20px ${fontStack}`;
                        ctx.fillText(percent + '%', cx, cy + 2);
                        ctx.fillStyle = '#475569';
                        ctx.font = `700 11px ${fontStack}`;
                        ctx.fillText(money(amount) + ' ฿', cx, cy + 22);
                        ctx.restore();
                    }
                };

                function destroyChart(id) {
                    if (charts[id]) {
                        charts[id].destroy();
                        delete charts[id];
                    }
                }

                function renderDonut(id, percent, amount, accentHex) {
                    const canvas = document.getElementById(id);
                    if (!canvas || typeof Chart === 'undefined') return;
                    destroyChart(id);

                    const p = Math.max(0, Math.min(100, Math.round(percent)));
                    const data = p <= 0 ? [0, 100] : p >= 100 ? [100, 0] : [p, 100 - p];

                    charts[id] = new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            datasets: [{
                                data,
                                backgroundColor: [accentHex, '#e2e8f0'],
                                borderWidth: 0,
                                hoverBorderWidth: 0,
                                hoverOffset: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '64%',
                            rotation: 0,
                            circumference: 360,
                            layout: {
                                padding: 2
                            },
                            plugins: {
                                custom: {
                                    percent: p,
                                    amount,
                                    accent: accentHex
                                },
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    enabled: false
                                }
                            },
                            animation: {
                                animateRotate: true,
                                duration: 1000,
                                easing: 'easeOutCubic'
                            }
                        },
                        plugins: [centerTextPlugin]
                    });
                }

                function moneyTooltip(ctx) {
                    const label = ctx.dataset.label ? ctx.dataset.label + ': ' : '';
                    return label + money(ctx.raw) + ' ฿';
                }

                function commonScales(stacked = false) {
                    return {
                        x: {
                            stacked,
                            ticks: {
                                font: {
                                    family: fontStack,
                                    size: 11
                                },
                                color: '#64748b',
                                maxRotation: 0,
                                callback: function(value) {
                                    const label = this.getLabelForValue(value);
                                    if (typeof label !== 'string') return label;
                                    return label.length > 14 ? label.slice(0, 13) + '…' : label;
                                }
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked,
                            ticks: {
                                font: {
                                    family: fontStack,
                                    size: 11
                                },
                                color: '#64748b',
                                callback: (v) => moneyShort(v)
                            },
                            grid: {
                                color: 'rgba(148,163,184,0.18)'
                            }
                        }
                    };
                }

                function renderBar(id, config) {
                    const canvas = document.getElementById(id);
                    if (!canvas || typeof Chart === 'undefined') return;
                    destroyChart(id);
                    charts[id] = new Chart(canvas, {
                        type: config.type || 'bar',
                        data: {
                            labels: config.labels || [],
                            datasets: config.datasets || []
                        },
                        options: {
                            indexAxis: config.indexAxis || 'x',
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 12,
                                        boxHeight: 12,
                                        usePointStyle: true,
                                        font: {
                                            family: fontStack,
                                            size: 11
                                        },
                                        color: '#475569'
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: moneyTooltip
                                    }
                                }
                            },
                            scales: config.scales || commonScales(!!config.stacked),
                            animation: {
                                duration: 900,
                                easing: 'easeOutCubic'
                            }
                        }
                    });
                }

                function renderStatusMix() {
                    const canvas = document.getElementById('chartStatusMix');
                    if (!canvas || typeof Chart === 'undefined') return;
                    destroyChart('chartStatusMix');
                    const data = chartsData.status_mix || {
                        labels: [],
                        values: []
                    };
                    charts.chartStatusMix = new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.values,
                                backgroundColor: ['#059669', '#d97706', '#64748b'],
                                borderWidth: 0,
                                hoverOffset: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '55%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 12,
                                        usePointStyle: true,
                                        font: {
                                            family: fontStack,
                                            size: 12
                                        },
                                        color: '#475569'
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => {
                                            const total = (ctx.dataset.data || []).reduce((a, b) => a + Number(b || 0), 0);
                                            const val = Number(ctx.raw || 0);
                                            const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                            return `${ctx.label}: ${money(val)} ฿ (${pct}%)`;
                                        }
                                    }
                                }
                            },
                            animation: {
                                duration: 1000,
                                easing: 'easeOutCubic'
                            }
                        }
                    });
                }

                function bootAnalytics() {
                    const sc = chartsData.source_compare || {};
                    renderBar('chartSourceCompare', {
                        labels: sc.labels || [],
                        datasets: [{
                                label: 'ยอดปัจจุบัน',
                                data: sc.balance || [],
                                backgroundColor: '#3b82f6',
                                borderRadius: 6,
                                maxBarThickness: 28
                            },
                            {
                                label: 'เบิกจ่ายแล้ว',
                                data: sc.withdraw || [],
                                backgroundColor: '#059669',
                                borderRadius: 6,
                                maxBarThickness: 28
                            },
                            {
                                label: 'รอเบิกจ่าย',
                                data: sc.wait || [],
                                backgroundColor: '#d97706',
                                borderRadius: 6,
                                maxBarThickness: 28
                            },
                            {
                                label: 'คงเหลือ',
                                data: sc.remain || [],
                                backgroundColor: '#94a3b8',
                                borderRadius: 6,
                                maxBarThickness: 28
                            }
                        ]
                    });

                    renderStatusMix();

                    const byYear = chartsData.by_year || {};
                    renderBar('chartByYear', {
                        labels: byYear.labels || [],
                        datasets: [{
                                label: 'ยอดปัจจุบัน',
                                data: byYear.balance || [],
                                backgroundColor: '#2563eb',
                                borderRadius: 6,
                                maxBarThickness: 36
                            },
                            {
                                label: 'เบิกจ่ายแล้ว',
                                data: byYear.withdraw || [],
                                backgroundColor: '#10b981',
                                borderRadius: 6,
                                maxBarThickness: 36
                            },
                            {
                                label: 'คงเหลือ',
                                data: byYear.remain || [],
                                backgroundColor: '#cbd5e1',
                                borderRadius: 6,
                                maxBarThickness: 36
                            }
                        ]
                    });

                    const monthly = chartsData.monthly_withdraw || {};
                    renderBar('chartMonthly', {
                        type: 'line',
                        labels: monthly.labels || [],
                        datasets: [{
                                label: 'อนุมัติแล้ว',
                                data: monthly.approved || [],
                                borderColor: '#059669',
                                backgroundColor: 'rgba(5,150,105,0.12)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                borderWidth: 2.5
                            },
                            {
                                label: 'รออนุมัติ',
                                data: monthly.pending || [],
                                borderColor: '#d97706',
                                backgroundColor: 'rgba(217,119,6,0.10)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                borderWidth: 2.5
                            }
                        ],
                        scales: {
                            x: {
                                ticks: {
                                    font: {
                                        family: fontStack,
                                        size: 11
                                    },
                                    color: '#64748b'
                                },
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                ticks: {
                                    font: {
                                        family: fontStack,
                                        size: 11
                                    },
                                    color: '#64748b',
                                    callback: (v) => moneyShort(v)
                                },
                                grid: {
                                    color: 'rgba(148,163,184,0.18)'
                                }
                            }
                        }
                    });

                    const byCategory = chartsData.by_category || {};
                    renderBar('chartByCategory', {
                        indexAxis: 'y',
                        labels: byCategory.labels || [],
                        datasets: [{
                                label: 'ยอดปัจจุบัน',
                                data: byCategory.balance || [],
                                backgroundColor: '#3b82f6',
                                borderRadius: 5,
                                maxBarThickness: 18
                            },
                            {
                                label: 'เบิกจ่ายแล้ว',
                                data: byCategory.withdraw || [],
                                backgroundColor: '#059669',
                                borderRadius: 5,
                                maxBarThickness: 18
                            },
                            {
                                label: 'จัดแผนแล้ว',
                                data: byCategory.plan_used || [],
                                backgroundColor: '#8b5cf6',
                                borderRadius: 5,
                                maxBarThickness: 18
                            }
                        ],
                        scales: {
                            x: {
                                ticks: {
                                    font: {
                                        family: fontStack,
                                        size: 11
                                    },
                                    color: '#64748b',
                                    callback: (v) => moneyShort(v)
                                },
                                grid: {
                                    color: 'rgba(148,163,184,0.18)'
                                }
                            },
                            y: {
                                ticks: {
                                    font: {
                                        family: fontStack,
                                        size: 11
                                    },
                                    color: '#475569',
                                    callback: function(value) {
                                        const label = this.getLabelForValue(value);
                                        if (typeof label !== 'string') return label;
                                        return label.length > 22 ? label.slice(0, 21) + '…' : label;
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    });

                    const byPlan = chartsData.by_plan || {};
                    renderBar('chartByPlan', {
                        indexAxis: 'y',
                        labels: byPlan.labels || [],
                        datasets: [{
                                label: 'ยอดปัจจุบัน',
                                data: byPlan.balance || [],
                                backgroundColor: '#0ea5e9',
                                borderRadius: 5,
                                maxBarThickness: 18
                            },
                            {
                                label: 'เบิกจ่ายแล้ว',
                                data: byPlan.withdraw || [],
                                backgroundColor: '#059669',
                                borderRadius: 5,
                                maxBarThickness: 18
                            }
                        ],
                        scales: {
                            x: {
                                ticks: {
                                    font: {
                                        family: fontStack,
                                        size: 11
                                    },
                                    color: '#64748b',
                                    callback: (v) => moneyShort(v)
                                },
                                grid: {
                                    color: 'rgba(148,163,184,0.18)'
                                }
                            },
                            y: {
                                ticks: {
                                    font: {
                                        family: fontStack,
                                        size: 11
                                    },
                                    color: '#475569',
                                    callback: function(value) {
                                        const label = this.getLabelForValue(value);
                                        if (typeof label !== 'string') return label;
                                        return label.length > 22 ? label.slice(0, 21) + '…' : label;
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    });

                    const byFund = chartsData.by_fund || {};
                    renderBar('chartByFund', {
                        labels: byFund.labels || [],
                        datasets: [{
                                label: 'จัดแผนแล้ว',
                                data: byFund.plan_used || [],
                                backgroundColor: '#8b5cf6',
                                borderRadius: 6,
                                maxBarThickness: 28
                            },
                            {
                                label: 'เบิกจ่ายแล้ว',
                                data: byFund.withdraw || [],
                                backgroundColor: '#059669',
                                borderRadius: 6,
                                maxBarThickness: 28
                            },
                            {
                                label: 'รอเบิกจ่าย',
                                data: byFund.wait || [],
                                backgroundColor: '#f59e0b',
                                borderRadius: 6,
                                maxBarThickness: 28
                            }
                        ]
                    });
                }

                function boot() {
                    const tw = summary.total?.withdraw ?? 0;
                    renderDonut('chartTotal', chartPercents.total, tw, '#2563eb');
                    renderDonut('chartIncome', chartPercents.income, summary.income?.withdraw ?? 0, '#16a34a');
                    renderDonut('chartGov', chartPercents.gov, summary.gov?.withdraw ?? 0, '#ca8a04');
                    renderDonut('chartExternal', chartPercents.external, summary.external?.withdraw ?? 0, '#e11d48');
                    bootAnalytics();
                }

                let resizeTimer;
                window.addEventListener('resize', function() {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(boot, 180);
                });

                function whenChartReady(attempt) {
                    if (typeof Chart !== 'undefined') {
                        boot();
                        return;
                    }
                    if (attempt > 40) return;
                    setTimeout(() => whenChartReady(attempt + 1), 100);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => whenChartReady(0));
                } else {
                    whenChartReady(0);
                }
            })();
        </script>
    @endpush
</x-layouts.app>
