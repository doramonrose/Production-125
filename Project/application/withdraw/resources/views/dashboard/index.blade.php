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
    $kpis = $report['kpis'];
    $yearCaption = $selectedYear === ''
        ? 'ทุกปีงบประมาณ'
        : 'ปีงบประมาณ '.$selectedYear.($selectedYear === $currentYear ? ' (ปัจจุบัน)' : '');
    $menuRoutes = [
        'withdraw-general' => route('withdraw.general.index'),
        'withdraw-project' => route('withdraw.project.index'),
        'withdraw-salary' => route('withdraw.salary.index'),
    ];
    $toneClass = [
        'blue' => 'text-blue-600',
        'emerald' => 'text-emerald-600',
        'violet' => 'text-violet-600',
    ];
    $statusIcon = static function (string $paths): string {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';
    };
    $statusCards = [
        ['text' => 'text-slate-700', 'color' => '#64748b', 'tint' => 'rgba(100,116,139,0.16)', 'hint' => 'บันทึกแล้ว ยังไม่เข้าคิวตรวจ', 'href' => null, 'icon' => $statusIcon('<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M8 13h8"/><path d="M8 17h5"/>')],
        ['text' => 'text-amber-700', 'color' => '#d97706', 'tint' => 'rgba(245,158,11,0.18)', 'hint' => 'อยู่ระหว่างตรวจสอบเอกสาร', 'href' => route('withdraw.document-review.index'), 'icon' => $statusIcon('<circle cx="11" cy="11" r="6.5"/><path d="m20 20-3.8-3.8"/>')],
        ['text' => 'text-violet-700', 'color' => '#7c3aed', 'tint' => 'rgba(139,92,246,0.18)', 'hint' => 'ถึงขั้นผู้บริหารแล้ว', 'href' => route('withdraw.approval.index'), 'icon' => $statusIcon('<circle cx="12" cy="12" r="8"/><path d="M12 8v4.5l2.5 1.5"/>')],
        ['text' => 'text-emerald-700', 'color' => '#059669', 'tint' => 'rgba(16,185,129,0.18)', 'hint' => 'อนุมัติครบขั้นตอน', 'href' => null, 'icon' => $statusIcon('<circle cx="12" cy="12" r="8"/><path d="m8.5 12.2 2.3 2.3 4.7-5"/>')],
        ['text' => 'text-rose-700', 'color' => '#e11d48', 'tint' => 'rgba(244,63,94,0.16)', 'hint' => 'ไม่นับในวงเงินที่ขอเบิก', 'href' => null, 'icon' => $statusIcon('<circle cx="12" cy="12" r="8"/><path d="m7.2 7.2 9.6 9.6"/>')],
    ];
    $statusTotal = array_sum($report['status_counts']);
    $statusAmountTotal = array_sum(array_map(static fn ($amount): float => (float) $amount, $report['status_amounts']));
    $pipelineCount = (int) ($report['status_counts'][0] ?? 0) + (int) ($report['status_counts'][1] ?? 0) + (int) ($report['status_counts'][2] ?? 0);
    $pipelineAmount = (float) ($report['status_amounts'][0] ?? 0) + (float) ($report['status_amounts'][1] ?? 0) + (float) ($report['status_amounts'][2] ?? 0);
@endphp

<x-layouts.app :title="$title ?? 'Dashboard'">
    <style>
        .dashboard-glass {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            border-radius: 28px !important;
            background: transparent !important;
            border: 1px solid rgba(255, 255, 255, 0.72) !important;
            box-shadow:
                0 18px 50px -28px rgba(15, 23, 42, 0.45),
                inset 0 1px 0 rgba(255, 255, 255, 0.92),
                inset 0 -1px 0 rgba(255, 255, 255, 0.28) !important;
        }
        .dashboard-glass::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.62), rgba(255, 255, 255, 0.28));
            backdrop-filter: blur(26px) saturate(180%);
            -webkit-backdrop-filter: blur(26px) saturate(180%);
        }
        .dashboard-glass::after {
            content: "";
            position: absolute;
            top: 0;
            left: 14%;
            right: 14%;
            z-index: 2;
            height: 1px;
            pointer-events: none;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.95), transparent);
        }
        .dashboard-glass > * {
            position: relative;
            z-index: 1;
        }
        .dashboard-glass-header::before {
            background:
                radial-gradient(circle at 0% 0%, rgba(16, 185, 129, 0.42), transparent 58%),
                radial-gradient(circle at 100% 80%, rgba(59, 130, 246, 0.36), transparent 62%),
                linear-gradient(115deg, rgba(16, 185, 129, 0.16), rgba(59, 130, 246, 0.12) 42%, rgba(255, 255, 255, 0.42));
        }
        .dashboard-chip {
            border-radius: 18px !important;
            border: 1px solid rgba(255, 255, 255, 0.7) !important;
            background: rgba(255, 255, 255, 0.46) !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85), 0 8px 20px -16px rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(16px) saturate(160%);
            -webkit-backdrop-filter: blur(16px) saturate(160%);
        }
        .dashboard-donut-ring {
            background: radial-gradient(circle at 50% 40%, rgba(255, 255, 255, 0.72), rgba(255, 255, 255, 0.28));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 10px 30px -18px rgba(37, 99, 235, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.75);
        }
        .dashboard-summary-card {
            padding: 22px 22px 14px;
        }
        .dashboard-summary {
            display: grid;
            grid-template-columns: minmax(240px, 300px) minmax(0, 1fr);
            gap: 22px;
            align-items: start;
        }
        .dashboard-summary-kicker {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
        }
        .dashboard-summary-total {
            margin: 8px 0 0;
            font-size: 44px;
            line-height: 0.95;
            font-weight: 900;
            letter-spacing: -0.04em;
            color: #0f172a;
            font-variant-numeric: tabular-nums;
        }
        .dashboard-summary-caption {
            margin: 8px 0 0;
            font-size: 12px;
            line-height: 1.45;
            color: #64748b;
        }
        .dashboard-mix {
            display: flex;
            gap: 3px;
            height: 12px;
            margin-top: 18px;
        }
        .dashboard-mix span {
            display: block;
            min-width: 8px;
            border-radius: 999px;
        }
        .dashboard-summary-facts {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin: 16px 0 0;
            padding: 0;
            list-style: none;
        }
        .dashboard-summary-facts b {
            display: block;
            font-size: 16px;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }
        .dashboard-summary-facts span {
            display: block;
            margin-top: 3px;
            font-size: 11px;
            color: #64748b;
        }
        .dashboard-summary-open {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.85);
        }
        .dashboard-summary-open p {
            margin: 0;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
        }
        .dashboard-summary-open strong {
            display: block;
            margin-top: 4px;
            font-size: 18px;
            line-height: 1.15;
            font-variant-numeric: tabular-nums;
            color: #0f172a;
        }
        .dashboard-summary-open em {
            display: block;
            margin-top: 2px;
            font-style: normal;
            font-size: 11px;
            color: #64748b;
        }
        .dashboard-summary-list {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .dashboard-summary-list-note {
            margin: 2px 12px 6px;
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
        }
        .dashboard-summary-row {
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr) auto;
            column-gap: 12px;
            row-gap: 7px;
            align-items: center;
            padding: 10px 12px;
            border-radius: 18px;
            color: inherit;
            text-decoration: none;
        }
        a.dashboard-summary-row:hover {
            background: rgba(255, 255, 255, 0.7);
        }
        .dashboard-status-icon {
            display: inline-flex;
            grid-row: 1 / span 2;
            align-items: center;
            align-self: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 12px;
        }
        .dashboard-status-icon svg {
            display: block;
            width: 18px;
            height: 18px;
        }
        .dashboard-summary-copy {
            min-width: 0;
        }
        .dashboard-summary-name {
            margin: 0;
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
        }
        .dashboard-summary-hint {
            margin: 2px 0 0;
            overflow: hidden;
            font-size: 11px;
            line-height: 1.35;
            color: #64748b;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dashboard-summary-count {
            font-size: 22px;
            font-weight: 900;
            line-height: 1;
            font-variant-numeric: tabular-nums;
            text-align: right;
        }
        .dashboard-summary-share {
            grid-column: 3;
            grid-row: 2;
            margin: 0;
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-align: right;
            white-space: nowrap;
        }
        .dashboard-summary-amount {
            font-weight: 700;
            color: #334155;
        }
        .dashboard-status-track {
            grid-column: 2;
            grid-row: 2;
            height: 6px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.07);
        }
        .dashboard-status-fill {
            height: 100%;
            border-radius: inherit;
        }
        @media (max-width: 860px) {
            .dashboard-summary {
                grid-template-columns: 1fr;
            }
        }
        .dashboard-kpi-panel {
            padding: 8px;
        }
        .dashboard-kpi-grid {
            display: grid;
            grid-template-columns: 1.25fr repeat(3, minmax(0, 1fr));
            gap: 8px;
        }
        .dashboard-kpi {
            display: flex;
            min-width: 0;
            flex-direction: column;
            padding: 16px 16px 14px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.38);
            color: inherit;
            text-decoration: none;
        }
        .dashboard-kpi-main {
            background: rgba(255, 255, 255, 0.72);
        }
        a.dashboard-kpi:hover {
            background: rgba(255, 255, 255, 0.78);
        }
        .dashboard-kpi-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            font-size: 12px;
            font-weight: 800;
        }
        .dashboard-kpi-mark {
            display: inline-flex;
            flex: 0 0 28px;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 9px;
        }
        .dashboard-kpi-mark svg {
            display: block;
            width: 15px;
            height: 15px;
        }
        .dashboard-kpi-value {
            margin: 12px 0 0;
            font-size: clamp(20px, 1.7vw, 28px);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.03em;
            font-variant-numeric: tabular-nums;
        }
        .dashboard-kpi-main .dashboard-kpi-value {
            font-size: clamp(26px, 2.1vw, 34px);
        }
        .dashboard-kpi-meta {
            margin: 6px 0 0;
            font-size: 12px;
            line-height: 1.4;
            color: #64748b;
        }
        .dashboard-kpi-meter {
            height: 6px;
            margin-top: 14px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.08);
        }
        .dashboard-kpi-meter span {
            display: block;
            height: 100%;
            border-radius: inherit;
        }
        .dashboard-type-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .dashboard-type-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 132px;
            gap: 8px;
            align-items: center;
            padding: 18px 16px 16px 18px;
        }
        .dashboard-type-kicker {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
        }
        .dashboard-type-kicker i {
            width: 8px;
            height: 8px;
            border-radius: 99px;
        }
        .dashboard-type-amount {
            margin: 8px 0 0;
            font-size: clamp(20px, 1.6vw, 26px);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.03em;
            font-variant-numeric: tabular-nums;
        }
        .dashboard-type-meta,
        .dashboard-type-approved {
            margin: 4px 0 0;
            font-size: 12px;
            color: #64748b;
        }
        .dashboard-type-meter {
            height: 6px;
            margin-top: 12px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.08);
        }
        .dashboard-type-meter span {
            display: block;
            height: 100%;
            border-radius: inherit;
        }
        .dashboard-type-link {
            display: inline-flex;
            align-items: center;
            margin-top: 12px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.9);
            font-size: 12px;
            font-weight: 800;
            color: #047857;
            text-decoration: none;
        }
        .dashboard-type-link:hover {
            background: #fff;
        }
        .dashboard-type-chart {
            position: relative;
            width: 132px;
            height: 132px;
        }
        @media (max-width: 1020px) {
            .dashboard-kpi-grid,
            .dashboard-type-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 680px) {
            .dashboard-kpi-grid,
            .dashboard-type-grid,
            .dashboard-type-card {
                grid-template-columns: 1fr;
            }
            .dashboard-type-chart {
                margin: 4px auto 0;
            }
        }
    </style>
    <div class="py-2">
        <div class="dashboard-glass dashboard-glass-header mx-2 mb-4">
            <div class="relative px-5 py-3 sm:px-6 sm:py-4 lg:px-8 lg:py-5">
                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl !bg-[linear-gradient(135deg,#059669,#2563eb)] text-white shadow-lg shadow-emerald-500/25 ring-1 ring-white/20">
                                <i class="fas fa-house text-[13px] drop-shadow-[0_1px_2px_rgba(0,0,0,0.25)]"></i>
                            </span>
                            <h2 class="truncate text-[22px] font-black tracking-tight text-slate-950 lg:text-[28px]">Dashboard</h2>
                        </div>
                        <p class="mt-1 max-w-[58ch] text-xs leading-relaxed text-slate-600 lg:text-sm">
                            ภาพรวมการเบิกจ่าย{{ $yearCaption }} สำหรับผู้บริหารติดตามสถานะได้ทันที
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 lg:shrink-0 lg:justify-end">
                        @if ($canMenu('withdraw-general'))
                            <a href="{{ route('withdraw.general.index') }}"
                                class="dashboard-chip inline-flex h-10 items-center justify-center gap-2 px-4 text-sm font-bold text-emerald-800 transition hover:-translate-y-0.5">
                                <i class="fas fa-list-check text-emerald-600"></i>
                                เบิกจ่ายทั่วไป
                            </a>
                        @endif
                        @if ($canMenu('withdraw-project'))
                            <a href="{{ route('withdraw.project.index') }}"
                                class="dashboard-chip inline-flex h-10 items-center justify-center gap-2 px-4 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5">
                                <i class="fas fa-diagram-project text-slate-500"></i>
                                เงินโครงการ
                            </a>
                        @endif
                        @if ($canMenu('withdraw-salary'))
                            <a href="{{ route('withdraw.salary.index') }}"
                                class="dashboard-chip inline-flex h-10 items-center justify-center gap-2 px-4 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5">
                                <i class="fas fa-coins text-slate-500"></i>
                                เงินเดือน/ค่าจ้าง
                            </a>
                        @endif
                        <label for="dashboard-year-filter" class="sr-only">ปีงบประมาณ</label>
                        <div class="relative inline-flex">
                            <select id="dashboard-year-filter"
                                class="dashboard-year-select dashboard-chip h-10 cursor-pointer py-2 pl-3.5 pr-10 text-sm font-semibold text-slate-700 outline-none"
                                onchange="window.location.href=this.value">
                                <option value="{{ route('dashboard', ['year' => 'all']) }}" @selected($selectedYear === '')>
                                    ปีงบทั้งหมด
                                </option>
                                @foreach ($yearOptions as $year)
                                    <option value="{{ route('dashboard', ['year' => $year]) }}" @selected($selectedYear === $year)>
                                        ปีงบ {{ $year }}{{ $year === $currentYear ? ' (ปัจจุบัน)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex w-9 items-center justify-center text-slate-400">
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
                            select.dashboard-year-select::-ms-expand { display: none; }
                        </style>
                    </div>
                </div>
            </div>
        </div>

        @php
            $kpiShare = static function (float $part, float $whole): float {
                if ($whole <= 0) {
                    return 0.0;
                }

                return max(0, min(100, ($part / $whole) * 100));
            };
            $requestedAmount = (float) $kpis['active_amount'];
            $kpiItems = [
                [
                    'label' => 'วงเงินที่ขอเบิก',
                    'value' => $fmtMoney($requestedAmount),
                    'meta' => number_format($kpis['active_count']).' รายการที่ยังไม่ยกเลิก',
                    'color' => '#2563eb',
                    'tint' => 'rgba(37,99,235,0.14)',
                    'share' => 100,
                    'href' => null,
                    'main' => true,
                    'icon' => $statusIcon('<ellipse cx="12" cy="7" rx="7" ry="3"/><path d="M5 7v5c0 1.7 3.1 3 7 3s7-1.3 7-3V7"/><path d="M5 12v5c0 1.7 3.1 3 7 3s7-1.3 7-3v-5"/>'),
                ],
                [
                    'label' => 'อนุมัติแล้ว',
                    'value' => $fmtMoney($kpis['approved_amount']),
                    'meta' => number_format($kpis['approved_count']).' รายการ · '.$kpis['approved_pct'].'% ของวงเงิน',
                    'color' => '#059669',
                    'tint' => 'rgba(16,185,129,0.16)',
                    'share' => $kpiShare((float) $kpis['approved_amount'], $requestedAmount),
                    'href' => null,
                    'main' => false,
                    'icon' => $statusIcon('<circle cx="12" cy="12" r="8"/><path d="m8.5 12.2 2.3 2.3 4.7-5"/>'),
                ],
                [
                    'label' => 'รออนุมัติ',
                    'value' => $fmtMoney($kpis['waiting_amount']),
                    'meta' => number_format($kpis['waiting_count']).' รายการถึงขั้นผู้บริหาร',
                    'color' => '#d97706',
                    'tint' => 'rgba(245,158,11,0.16)',
                    'share' => $kpiShare((float) $kpis['waiting_amount'], $requestedAmount),
                    'href' => route('withdraw.approval.index'),
                    'main' => false,
                    'icon' => $statusIcon('<circle cx="12" cy="12" r="8"/><path d="M12 8v4.5l2.5 1.5"/>'),
                ],
                [
                    'label' => 'ระหว่างตรวจสอบ',
                    'value' => $fmtMoney($kpis['review_amount']),
                    'meta' => number_format($kpis['review_count']).' รายการยังไม่ถึงขั้นอนุมัติ',
                    'color' => '#475569',
                    'tint' => 'rgba(100,116,139,0.16)',
                    'share' => $kpiShare((float) $kpis['review_amount'], $requestedAmount),
                    'href' => route('withdraw.document-review.index'),
                    'main' => false,
                    'icon' => $statusIcon('<circle cx="11" cy="11" r="6.5"/><path d="m20 20-3.8-3.8"/>'),
                ],
            ];
        @endphp
        <section class="dashboard-glass dashboard-kpi-panel mx-2 mb-4" aria-label="ตัวเลขหลัก">
            <div class="dashboard-kpi-grid">
                @foreach ($kpiItems as $item)
                    @php $kpiTag = $item['href'] ? 'a' : 'div'; @endphp
                    <{{ $kpiTag }} @if ($item['href']) href="{{ $item['href'] }}" @endif class="dashboard-kpi {{ $item['main'] ? 'dashboard-kpi-main' : '' }}">
                        <p class="dashboard-kpi-label" style="color: {{ $item['color'] }}">
                            <span class="dashboard-kpi-mark" style="background: {{ $item['tint'] }}; color: {{ $item['color'] }}">{!! $item['icon'] !!}</span>
                            {{ $item['label'] }}
                        </p>
                        <p class="dashboard-kpi-value" style="color: {{ $item['color'] }}">{{ $item['value'] }}</p>
                        <p class="dashboard-kpi-meta">{{ $item['meta'] }}@if ($item['href']) · เปิดรายการ @endif</p>
                        <div class="dashboard-kpi-meter" aria-hidden="true">
                            <span style="width: {{ $item['share'] }}%; background: {{ $item['color'] }}"></span>
                        </div>
                    </{{ $kpiTag }}>
                @endforeach
            </div>
        </section>

        @if ($report['donuts'] !== [])
            <div class="dashboard-type-grid mx-2 mb-4">
                @foreach ($report['donuts'] as $donut)
                    <article class="dashboard-glass dashboard-type-card">
                        <div class="min-w-0">
                            <h3 class="dashboard-type-kicker">
                                <i style="background: {{ $donut['accent'] }}"></i>
                                {{ $donut['label'] }}
                            </h3>
                            <p class="dashboard-type-amount" style="color: {{ $donut['accent'] }}">{{ $fmtMoney($donut['requested']) }}</p>
                            <p class="dashboard-type-meta">{{ number_format($donut['count']) }} รายการ · วงเงินที่ขอ</p>
                            <div class="dashboard-type-meter" aria-hidden="true">
                                <span style="width: {{ max(0, min(100, (float) $donut['percent'])) }}%; background: {{ $donut['accent'] }}"></span>
                            </div>
                            <p class="dashboard-type-approved">อนุมัติแล้ว {{ $fmtMoney($donut['approved']) }} บาท</p>
                            @if (! empty($donut['menu_key']) && $canMenu($donut['menu_key']))
                                <a href="{{ $menuRoutes[$donut['menu_key']] }}" class="dashboard-type-link">ดูรายการ</a>
                            @endif
                        </div>
                        <div class="dashboard-type-chart">
                            <canvas id="{{ $donut['id'] }}"></canvas>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <div class="mx-2 mb-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="dashboard-glass p-5">
                <div class="mb-3">
                    <h3 class="text-sm font-bold text-slate-800">เปรียบเทียบตามประเภท</h3>
                    <p class="mt-0.5 text-xs text-slate-500">วงเงินที่ขอ · อนุมัติแล้ว · รออนุมัติ · ระหว่างตรวจสอบ</p>
                </div>
                <div class="relative h-72">
                    <canvas id="chartTypeCompare"></canvas>
                </div>
            </div>

            <div class="dashboard-glass p-5">
                <div class="mb-3">
                    <h3 class="text-sm font-bold text-slate-800">สัดส่วนสถานะรายการ</h3>
                    <p class="mt-0.5 text-xs text-slate-500">นับตามจำนวนรายการ เพื่อไม่ให้เงินเดือนบดบังคิวงาน</p>
                </div>
                <div class="relative mx-auto h-72 max-w-md">
                    <canvas id="chartStatusMix"></canvas>
                </div>
            </div>

            <div class="dashboard-glass p-5">
                <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">แนวโน้มการเบิกจ่ายรายเดือน</h3>
                        <p class="mt-0.5 text-xs text-slate-500">ไม่รวมรายการที่ยกเลิก · แกนตั้งย่อเป็นล้านเมื่อวงเงินใหญ่</p>
                    </div>
                    <div class="dashboard-chip inline-flex p-1" role="group" aria-label="เลือกชุดข้อมูลกราฟรายเดือน">
                        <button type="button" data-monthly-mode="all" aria-pressed="true" class="dashboard-mode-btn cursor-pointer rounded-lg px-3 py-1.5 text-xs font-bold text-slate-800" style="background-color:#fff;box-shadow:0 1px 2px rgba(15,23,42,.08)">ทั้งหมด</button>
                        <button type="button" data-monthly-mode="operations" aria-pressed="false" class="dashboard-mode-btn cursor-pointer rounded-lg px-3 py-1.5 text-xs font-bold text-slate-500" style="background-color:transparent">ไม่รวมเงินเดือน</button>
                    </div>
                </div>
                <div class="relative h-72">
                    <canvas id="chartMonthly"></canvas>
                </div>
            </div>

            @php
                $rankCards = [
                    [
                        'title' => 'หมวดรายจ่าย',
                        'hint' => 'รวมตามหมวดของผังบัญชีที่เลือกในรายการเบิก ไม่รวมรายการที่ยกเลิก',
                        'canvas' => 'chartCategoryPay',
                        'series' => $report['category_pay'] ?? ['labels' => [], 'amounts' => [], 'counts' => []],
                        'unit' => 'รายการ',
                        'color' => '#2563eb',
                    ],
                    [
                        'title' => 'ส่วนงาน',
                        'hint' => 'ตามส่วนงานของผู้ขอเบิก ไม่รวมรายการที่ยกเลิก',
                        'canvas' => 'chartDepartment',
                        'series' => $report['departments'] ?? ['labels' => [], 'amounts' => [], 'counts' => []],
                        'unit' => 'เรื่อง',
                        'color' => '#059669',
                    ],
                ];
            @endphp
            @foreach ($rankCards as $rankCard)
                @php
                    $rankLabels = $rankCard['series']['labels'] ?? [];
                @endphp
                <section class="dashboard-glass p-5">
                    <div class="mb-3">
                        <h3 class="text-sm font-bold text-slate-800">{{ $rankCard['title'] }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $rankCard['hint'] }}</p>
                    </div>
                    @if ($rankLabels === [])
                        <p class="flex h-40 items-center justify-center text-sm text-slate-500">ยังไม่มีข้อมูล</p>
                    @else
                        <div class="relative h-72" style="height:18rem">
                            <canvas id="{{ $rankCard['canvas'] }}"></canvas>
                        </div>
                    @endif
                </section>
            @endforeach

            @if ($report['all_years'] && $report['by_year']['labels'] !== [])
                <div class="dashboard-glass p-5 xl:col-span-2">
                    <div class="mb-3">
                        <h3 class="text-sm font-bold text-slate-800">การเบิกจ่ายตามปีงบประมาณ</h3>
                        <p class="mt-0.5 text-xs text-slate-500">วงเงินที่ขอเทียบกับยอดที่อนุมัติแล้ว</p>
                    </div>
                    <div class="relative h-72">
                        <canvas id="chartByYear"></canvas>
                    </div>
                </div>
            @endif
        </div>

        <div class="mx-2">
            <section class="dashboard-glass dashboard-summary-card" aria-labelledby="dashboard-status-summary">
                <div class="dashboard-summary">
                    <div>
                        <h3 id="dashboard-status-summary" class="dashboard-summary-kicker">สรุปสถานะรายการเบิกจ่าย</h3>
                        <p class="dashboard-summary-total">{{ number_format($statusTotal) }}</p>
                        <p class="dashboard-summary-caption">รายการทั้งหมดใน{{ $yearCaption }} รวมที่ยกเลิก</p>
                        <div class="dashboard-mix" role="img" aria-label="สัดส่วนจำนวนรายการตามสถานะ">
                            @foreach ($report['status_labels'] as $index => $label)
                                @php
                                    $mixCount = (int) ($report['status_counts'][$index] ?? 0);
                                    $mixCard = $statusCards[$index] ?? $statusCards[0];
                                @endphp
                                @if ($mixCount > 0)
                                    <span style="flex: {{ $mixCount }} 1 0; background: {{ $mixCard['color'] }}" title="{{ $label }} {{ number_format($mixCount) }} รายการ"></span>
                                @endif
                            @endforeach
                        </div>
                        <ul class="dashboard-summary-facts">
                            <li>
                                <b class="text-slate-800">{{ number_format($pipelineCount) }}</b>
                                <span>ยังไม่จบ</span>
                            </li>
                            <li>
                                <b class="text-emerald-700">{{ number_format($report['status_counts'][3] ?? 0) }}</b>
                                <span>อนุมัติแล้ว</span>
                            </li>
                            <li>
                                <b class="text-rose-700">{{ number_format($report['status_counts'][4] ?? 0) }}</b>
                                <span>ยกเลิก</span>
                            </li>
                        </ul>
                        <div class="dashboard-summary-open">
                            <p>วงเงินที่ยังไม่จบขั้นตอน</p>
                            <strong>{{ $fmtMoney($pipelineAmount) }} บาท</strong>
                            <em>{{ number_format($pipelineCount) }} รายการ ตั้งแต่ร่างจนถึงรออนุมัติ</em>
                        </div>
                    </div>
                    <div class="dashboard-summary-list">
                        <p class="dashboard-summary-list-note">แถบสีคือสัดส่วนของยอดเงินในสรุปนี้</p>
                        @foreach ($report['status_labels'] as $index => $label)
                            @php
                                $card = $statusCards[$index] ?? $statusCards[0];
                                $count = (int) ($report['status_counts'][$index] ?? 0);
                                $amount = (float) ($report['status_amounts'][$index] ?? 0);
                                $share = $statusAmountTotal > 0 ? ($amount / $statusAmountTotal) * 100 : 0;
                                $shareLabel = $share > 0 && $share < 1 ? '<1%' : number_format($share, 0).'%';
                                $rowTag = $card['href'] ? 'a' : 'div';
                            @endphp
                            <{{ $rowTag }} @if ($card['href']) href="{{ $card['href'] }}" @endif class="dashboard-summary-row">
                                <span class="dashboard-status-icon" style="background: {{ $card['tint'] }}; color: {{ $card['color'] }}">
                                    {!! $card['icon'] !!}
                                </span>
                                <div class="dashboard-summary-copy">
                                    <p class="dashboard-summary-name">{{ $label }}</p>
                                    <p class="dashboard-summary-hint">{{ $card['hint'] }}@if ($card['href']) · เปิดรายการ @endif · <span class="dashboard-summary-amount">{{ $fmtMoney($amount) }} บาท</span></p>
                                </div>
                                <p class="dashboard-summary-count {{ $card['text'] }}">{{ number_format($count) }}</p>
                                <div class="dashboard-status-track">
                                    <div class="dashboard-status-fill" style="width: {{ min(100, max($share, $amount > 0 ? 1.2 : 0)) }}%; background: {{ $card['color'] }}"></div>
                                </div>
                                <p class="dashboard-summary-share">{{ $shareLabel }}</p>
                            </{{ $rowTag }}>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof Chart === 'undefined') return;

                const report = @json($report);
                const fontStack = '"K2D", "Sarabun", sans-serif';
                const money = (value) => {
                    const n = Number(value || 0);
                    const whole = Number.isFinite(n) && Math.abs(n - Math.round(n)) < 1e-9;
                    return n.toLocaleString('en-US', {
                        minimumFractionDigits: whole ? 0 : 2,
                        maximumFractionDigits: 2,
                    });
                };
                const moneyShort = (value) => {
                    const n = Number(value || 0);
                    const abs = Math.abs(n);
                    if (abs >= 1e6) return (n / 1e6).toFixed(abs >= 1e7 ? 1 : 2) + 'ล.';
                    if (abs >= 1e3) return (n / 1e3).toFixed(abs >= 1e4 ? 0 : 1) + 'พ.';
                    return money(n);
                };
                const charts = {};

                const centerTextPlugin = {
                    id: 'dashboardDonutCenter',
                    afterDraw(chart) {
                        const { ctx, chartArea } = chart;
                        if (!chartArea || chartArea.width <= 0) return;
                        const cx = chartArea.left + chartArea.width / 2;
                        const cy = chartArea.top + chartArea.height / 2;
                        const percent = chart.config.options.plugins?.custom?.percent ?? 0;
                        const accent = chart.config.options.plugins?.custom?.accent ?? '#64748b';
                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.fillStyle = '#94a3b8';
                        ctx.font = `400 10px ${fontStack}`;
                        ctx.fillText('อนุมัติแล้ว', cx, cy - 10);
                        ctx.fillStyle = accent;
                        ctx.font = `700 22px ${fontStack}`;
                        ctx.fillText(percent + '%', cx, cy + 14);
                        ctx.restore();
                    },
                };

                const renderDonut = (donut) => {
                    const canvas = document.getElementById(donut.id);
                    if (!canvas) return;
                    const percent = Math.max(0, Math.min(100, Math.round(donut.percent || 0)));
                    const data = percent <= 0 ? [0, 100] : percent >= 100 ? [100, 0] : [percent, 100 - percent];
                    charts[donut.id] = new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            datasets: [{
                                data,
                                backgroundColor: [donut.accent, '#e2e8f0'],
                                borderWidth: 0,
                                hoverOffset: 6,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '74%',
                            plugins: {
                                custom: { percent, accent: donut.accent },
                                legend: { display: false },
                                tooltip: { enabled: false },
                            },
                            animation: { duration: 900, easing: 'easeOutCubic' },
                        },
                        plugins: [centerTextPlugin],
                    });
                };

                const moneyTooltip = (ctx) => {
                    const label = ctx.dataset.label ? ctx.dataset.label + ': ' : '';
                    return label + money(ctx.raw) + ' ฿';
                };
                const scales = (stacked = false) => ({
                    x: {
                        stacked,
                        ticks: { font: { family: fontStack, size: 11 }, color: '#64748b', maxRotation: 0 },
                        grid: { display: false },
                    },
                    y: {
                        stacked,
                        ticks: {
                            font: { family: fontStack, size: 11 },
                            color: '#64748b',
                            callback: (value) => moneyShort(value),
                        },
                        grid: { color: 'rgba(148,163,184,0.18)' },
                    },
                });
                const legend = {
                    position: 'bottom',
                    labels: {
                        boxWidth: 8,
                        usePointStyle: true,
                        font: { family: fontStack, size: 11 },
                        color: '#475569',
                    },
                };

                (report.donuts || []).forEach(renderDonut);

                const compare = document.getElementById('chartTypeCompare');
                if (compare) {
                    charts.chartTypeCompare = new Chart(compare, {
                        type: 'bar',
                        data: {
                            labels: report.type_labels,
                            datasets: [
                                { label: 'วงเงินที่ขอ', data: report.type_compare.requested, backgroundColor: '#3b82f6', borderRadius: 6, maxBarThickness: 22 },
                                { label: 'อนุมัติแล้ว', data: report.type_compare.approved, backgroundColor: '#059669', borderRadius: 6, maxBarThickness: 22 },
                                { label: 'รออนุมัติ', data: report.type_compare.waiting, backgroundColor: '#d97706', borderRadius: 6, maxBarThickness: 22 },
                                { label: 'ระหว่างตรวจสอบ', data: report.type_compare.review, backgroundColor: '#94a3b8', borderRadius: 6, maxBarThickness: 22 },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend, tooltip: { callbacks: { label: moneyTooltip } } },
                            scales: scales(false),
                        },
                    });
                }

                const status = document.getElementById('chartStatusMix');
                if (status) {
                    charts.chartStatusMix = new Chart(status, {
                        type: 'doughnut',
                        data: {
                            labels: report.status_labels,
                            datasets: [{
                                data: report.status_counts,
                                backgroundColor: ['#94a3b8', '#d97706', '#7c3aed', '#059669', '#e11d48'],
                                borderWidth: 0,
                                hoverOffset: 8,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '55%',
                            plugins: {
                                legend,
                                tooltip: {
                                    callbacks: {
                                        label(ctx) {
                                            const total = (ctx.dataset.data || []).reduce((sum, item) => sum + Number(item || 0), 0);
                                            const count = Number(ctx.raw || 0);
                                            const pct = total > 0 ? Math.round((count / total) * 100) : 0;
                                            const amount = report.status_amounts[ctx.dataIndex] || 0;
                                            return `${ctx.label}: ${count.toLocaleString('en-US')} รายการ (${pct}%) · ${money(amount)} ฿`;
                                        },
                                    },
                                },
                            },
                        },
                    });
                }

                const monthly = document.getElementById('chartMonthly');
                let monthlyChart = null;
                if (monthly) {
                    monthlyChart = new Chart(monthly, {
                        type: 'bar',
                        data: {
                            labels: report.month_labels,
                            datasets: [
                                { label: 'เบิกจ่ายทั่วไป', data: report.monthly.general, backgroundColor: '#3b82f6', borderRadius: 6, maxBarThickness: 28 },
                                { label: 'เบิกจ่ายเงินโครงการ', data: report.monthly.project, backgroundColor: '#059669', borderRadius: 6, maxBarThickness: 28 },
                                { label: 'เบิกจ่ายเงินเดือน/ค่าจ้าง', data: report.monthly.salary, backgroundColor: '#7c3aed', borderRadius: 6, maxBarThickness: 28 },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend, tooltip: { callbacks: { label: moneyTooltip } } },
                            scales: scales(true),
                        },
                    });
                }

                const byYear = document.getElementById('chartByYear');
                if (byYear) {
                    charts.chartByYear = new Chart(byYear, {
                        type: 'bar',
                        data: {
                            labels: report.by_year.labels,
                            datasets: [
                                { label: 'วงเงินที่ขอ', data: report.by_year.requested, backgroundColor: '#3b82f6', borderRadius: 6, maxBarThickness: 28 },
                                { label: 'อนุมัติแล้ว', data: report.by_year.approved, backgroundColor: '#059669', borderRadius: 6, maxBarThickness: 28 },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend, tooltip: { callbacks: { label: moneyTooltip } } },
                            scales: scales(false),
                        },
                    });
                }

                const renderRankChart = (canvasId, labels, amounts, counts, unit, color) => {
                    const canvas = document.getElementById(canvasId);
                    if (!canvas || !Array.isArray(labels) || labels.length === 0) return;

                    const rows = labels.map((label, index) => ({
                        label: String(label || ''),
                        amount: Number(amounts[index] || 0),
                        count: Number((counts || [])[index] || 0),
                    })).sort((left, right) => right.amount - left.amount);
                    const total = rows.reduce((sum, row) => sum + row.amount, 0);

                    try {
                        new Chart(canvas, {
                            type: 'bar',
                            data: {
                                labels: rows.map((row) => row.label),
                                datasets: [{
                                    label: 'วงเงินที่ขอ',
                                    data: rows.map((row) => row.amount),
                                    backgroundColor: color,
                                    borderRadius: 6,
                                    maxBarThickness: 42,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                layout: { padding: { top: 22 } },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            title(items) {
                                                const row = rows[items[0] ? items[0].dataIndex : 0];
                                                return row ? row.label : '';
                                            },
                                            label(context) {
                                                const row = rows[context.dataIndex] || { amount: 0, count: 0 };
                                                const percent = total > 0 ? ((row.amount / total) * 100).toFixed(1) : '0.0';
                                                const countText = row.count > 0
                                                    ? ' · ' + row.count.toLocaleString('en-US') + ' ' + unit
                                                    : '';
                                                return money(row.amount) + ' ฿' + countText + ' (' + percent + '%)';
                                            },
                                        },
                                    },
                                },
                                scales: {
                                    x: {
                                        ticks: {
                                            font: { family: fontStack, size: 11 },
                                            color: '#334155',
                                            maxRotation: 35,
                                            minRotation: 0,
                                            autoSkip: false,
                                            callback(value) {
                                                const label = this.getLabelForValue(value);
                                                if (typeof label !== 'string') return label;
                                                return label.length > 18 ? label.slice(0, 17) + '…' : label;
                                            },
                                        },
                                        grid: { display: false },
                                    },
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            font: { family: fontStack, size: 11 },
                                            color: '#64748b',
                                            callback: (value) => moneyShort(value),
                                        },
                                        grid: { color: 'rgba(148,163,184,0.18)' },
                                    },
                                },
                            },
                            plugins: [{
                                id: 'dashboardBarValue' + canvasId,
                                afterDatasetsDraw(chart) {
                                    try {
                                        const meta = chart.getDatasetMeta(0);
                                        if (!meta || !chart.chartArea) return;
                                        const ctx = chart.ctx;
                                        ctx.save();
                                        ctx.font = '600 11px ' + fontStack;
                                        ctx.fillStyle = '#1e293b';
                                        ctx.textAlign = 'center';
                                        ctx.textBaseline = 'bottom';
                                        meta.data.forEach((bar, index) => {
                                            if (!bar) return;
                                            const text = moneyShort(rows[index] ? rows[index].amount : 0);
                                            const y = Math.max(chart.chartArea.top + 12, bar.y - 4);
                                            ctx.fillText(text, bar.x, y);
                                        });
                                        ctx.restore();
                                    } catch (error) {}
                                },
                            }],
                        });
                    } catch (error) {}
                };

                renderRankChart('chartCategoryPay', (report.category_pay && report.category_pay.labels) || [], (report.category_pay && report.category_pay.amounts) || [], (report.category_pay && report.category_pay.counts) || [], 'รายการ', '#2563eb');
                renderRankChart('chartDepartment', (report.departments && report.departments.labels) || [], (report.departments && report.departments.amounts) || [], (report.departments && report.departments.counts) || [], 'เรื่อง', '#059669');

                const setMode = (mode) => {
                    if (!monthlyChart) return;
                    monthlyChart.setDatasetVisibility(2, mode !== 'operations');
                    monthlyChart.update();
                    document.querySelectorAll('.dashboard-mode-btn').forEach((button) => {
                        const active = button.dataset.monthlyMode === mode;
                        button.setAttribute('aria-pressed', active ? 'true' : 'false');
                        button.style.backgroundColor = active ? '#fff' : 'transparent';
                        button.style.boxShadow = active ? '0 1px 2px rgba(15,23,42,.08)' : 'none';
                        button.classList.toggle('text-slate-800', active);
                        button.classList.toggle('text-slate-500', !active);
                    });
                };
                document.querySelectorAll('[data-monthly-mode]').forEach((button) => {
                    button.addEventListener('click', () => setMode(button.dataset.monthlyMode || 'all'));
                });
            });
        </script>
    @endpush
</x-layouts.app>
