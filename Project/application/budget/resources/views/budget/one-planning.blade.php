<x-layouts.app>
    <div class="py-2">
        <div class="mx-2 mb-4 overflow-hidden rounded-[28px] bg-gradient-to-br from-emerald-500/[0.12] via-blue-500/[0.10] via-[40%] to-white/60 shadow-[0_8px_32px_-8px_rgba(15,23,42,0.22),inset_0_1px_0_rgba(255,255,255,0.5)] backdrop-blur-[22px] backdrop-saturate-[1.7]">
            <div class="relative px-5 py-3 sm:px-6 sm:py-4 lg:px-8 lg:py-5">
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute -top-16 -left-16 h-56 w-56 rounded-full opacity-55 bg-[radial-gradient(circle,rgba(16,185,129,0.40),transparent_65%)] blur-[44px]"></div>
                    <div class="absolute -bottom-20 -right-16 h-52 w-52 rounded-full opacity-50 bg-[radial-gradient(circle,rgba(59,130,246,0.38),transparent_65%)] blur-[44px]"></div>
                    <div class="absolute left-1/3 top-[-10px] h-36 w-72 rounded-full opacity-35 bg-[radial-gradient(circle,rgba(6,182,212,0.30),transparent_65%)] blur-[48px]"></div>
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/[0.85] to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-white/40 to-transparent"></div>
                </div>
                <div class="relative flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl !bg-[linear-gradient(135deg,#059669,#2563eb)] text-white shadow-lg shadow-emerald-500/25 ring-1 ring-white/20">
                                <i class="fas fa-wallet text-[13px]"></i>
                            </span>
                            <h2 class="truncate text-[22px] font-black tracking-tight text-slate-950 lg:text-[28px]">งบประมาณ One Planning</h2>
                        </div>
                        <p class="mt-1 max-w-[58ch] text-xs leading-relaxed text-slate-600 lg:text-sm">
                            รายการงบประมาณปี {{ $year }} จาก One Planning
                        </p>
                    </div>
                    @if ($year === $currentYear)
                        <span class="inline-flex h-10 w-fit items-center rounded-2xl border border-slate-900/10 bg-white/70 px-4 text-sm font-extrabold text-sky-700">ปีงบประมาณปัจจุบัน</span>
                    @endif
                </div>
            </div>
        </div>

        @error('fiscal_year_th') <p role="alert" class="mx-2 mb-4 text-red-600">กรุณาระบุปี พ.ศ. ระหว่าง 2500–2700</p> @enderror
        @if ($error)
            <div role="alert" class="mx-2 rounded-3xl border border-red-200 bg-red-50 p-4 text-red-600">{{ $error }}</div>
        @else
            <div class="mx-2 mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 text-center shadow-soft-xl">
                    <h6 class="text-sm font-bold text-slate-600">งบประมาณที่ได้รับจัดสรรตามรอบงบประมาณ</h6>
                    <h5 id="sum-amount" class="mt-1 text-xl font-bold text-blue-500">0</h5>
                    <p id="sum-count" class="mt-0.5 text-xs font-semibold text-slate-500">0 รายการ</p>
                </div>
                <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 text-center shadow-soft-xl">
                    <h6 class="text-sm font-bold text-slate-600">(+/-)</h6>
                    <h5 id="sum-delta" class="mt-1 text-xl font-bold text-slate-700">0</h5>
                    <p class="mt-0.5 text-xs font-semibold text-slate-500">งบประมาณคงเหลือเทียบกับงบประมาณที่ได้รับจัดสรร</p>
                </div>
                <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 text-center shadow-soft-xl">
                    <h6 class="text-sm font-bold text-slate-600">งบประมาณคงเหลือ</h6>
                    <h5 id="sum-balance" class="mt-1 text-xl font-bold text-green-500">0</h5>
                    <p class="mt-0.5 text-xs font-semibold text-slate-500">คงเหลือ ณ วันเวลาเรียกข้อมูล</p>
                </div>
            </div>

            <div class="mx-2 mb-4 rounded-3xl border border-slate-200/80 bg-white/95 p-5 shadow-soft-xl backdrop-blur-sm relative z-[80]">
                <form method="GET" action="{{ route('budget.one-planning.index') }}" class="mb-3 flex flex-wrap items-center gap-2">
                    <label for="fiscal-year" class="text-sm font-semibold text-slate-700">ปีงบประมาณ</label>
                    @if ($year > 2500)
                        <a href="{{ route('budget.one-planning.index', ['fiscal_year_th' => $year - 1]) }}" class="inline-flex h-11 shrink-0 items-center rounded-xl border border-blue-500/20 bg-white px-3 text-sm font-semibold text-blue-600 shadow-sm hover:bg-blue-50">ปีก่อน</a>
                    @endif
                    <select id="fiscal-year" name="fiscal_year_th" onchange="this.form.submit()" class="h-11 min-w-44 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm">
                        @foreach ($yearOptions as $option)
                            <option value="{{ $option }}" @selected((int) $option === (int) $year)>{{ $option }}{{ (int) $option === (int) $currentYear ? ' · ปีปัจจุบัน' : '' }}</option>
                        @endforeach
                    </select>
                    @if ($year < 2700)
                        <a href="{{ route('budget.one-planning.index', ['fiscal_year_th' => $year + 1]) }}" class="inline-flex h-11 shrink-0 items-center rounded-xl border border-blue-500/20 bg-white px-3 text-sm font-semibold text-blue-600 shadow-sm hover:bg-blue-50">ปีถัดไป</a>
                    @endif
                </form>

                <div class="mb-3 flex items-center gap-2">
                    <div id="type-filters" class="flex min-w-0 flex-1 items-center gap-2 overflow-x-auto pb-1">
                        <button type="button" data-filter-type="" class="type-filter-btn active inline-flex h-11 shrink-0 items-center rounded-xl border border-blue-500/20 bg-white px-3 text-sm font-semibold text-blue-600 shadow-sm hover:bg-blue-50">ทั้งหมด</button>
                        @foreach ($typeOptions as $type)
                            <button type="button" data-filter-type="{{ $type }}" class="type-filter-btn inline-flex h-11 shrink-0 items-center rounded-xl border border-blue-500/20 bg-white px-3 text-sm font-semibold text-blue-600 shadow-sm hover:bg-blue-50">{{ $type }}</button>
                        @endforeach
                    </div>
                    <div class="group relative w-48 shrink-0 sm:w-80">
                        <label for="filter-search" class="sr-only">ค้นหารหัสงบประมาณหรือชื่อของงบประมาณ</label>
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fas fa-search text-xs"></i>
                        </span>
                        <input id="filter-search" type="text" inputmode="search" placeholder="ค้นหารหัสงบประมาณ หรือชื่อของงบประมาณ..." class="w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-2.5 pl-9 pr-10 text-sm font-medium text-slate-700 shadow-sm placeholder:font-normal placeholder:text-slate-400 hover:border-slate-300 hover:bg-white focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-100" />
                        <button id="btn-clear-search" type="button" class="absolute inset-y-0 right-1.5 my-auto hidden h-7 w-7 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="ล้างคำค้นหา">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                </div>

                @php
                    $asDropdown = static function (array $values): array {
                        return array_map(static fn (string $value): array => ['value' => $value, 'text' => $value], $values);
                    };
                @endphp
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                    <x-searchable-dropdown id="filter-type" placeholder="ประเภทงบประมาณ..." :options="$asDropdown($typeOptions)" valueKey="value" textKey="text" />
                    <x-searchable-dropdown id="filter-plan" placeholder="ค้นหาแผนงานมหาวิทยาลัย..." :options="$asDropdown($planOptions)" valueKey="value" textKey="text" />
                    <x-searchable-dropdown id="filter-fund" placeholder="ค้นหากองทุน..." :options="$asDropdown($fundOptions)" valueKey="value" textKey="text" />
                    <x-searchable-dropdown id="filter-category" placeholder="ค้นหาหมวดรายจ่าย..." :options="$asDropdown($categoryOptions)" valueKey="value" textKey="text" />
                    <x-searchable-dropdown id="filter-round" placeholder="ค้นหารอบงบประมาณ..." :options="$asDropdown($roundOptions)" valueKey="value" textKey="text" />
                    <button id="btn-reset-filter" type="button" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50">
                        <i class="fas fa-undo-alt mr-1"></i>รีเซ็ต
                    </button>
                </div>
            </div>

            <div class="mx-2 rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-soft-xl backdrop-blur-sm">
                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                    <table class="w-full table-auto text-sm [&_td]:align-top">
                        <caption class="sr-only">รายการงบประมาณ One Planning ปี {{ $year }}</caption>
                        <thead class="sticky top-0 z-10">
                            <tr class="border-b border-slate-200 bg-slate-50/95 text-[11px] font-semibold uppercase tracking-wider text-slate-700 backdrop-blur">
                                <th class="w-11 px-3 py-3 text-center align-middle">#</th>
                                <th class="cursor-pointer px-3 py-3 text-center align-middle hover:bg-slate-100/70" data-sort-key="code"><span class="inline-flex items-center justify-center gap-1">รหัสงบประมาณ<span class="text-[10px] text-slate-400" data-sort-indicator-for="code"></span></span></th>
                                <th class="cursor-pointer px-3 py-3 text-center align-middle hover:bg-slate-100/70" data-sort-key="name"><span class="inline-flex items-center justify-center gap-1">ชื่อของงบประมาณ<span class="text-[10px] text-slate-400" data-sort-indicator-for="name"></span></span></th>
                                <th class="cursor-pointer px-3 py-3 text-center align-middle hover:bg-slate-100/70" data-sort-key="budget_round_group_name"><span class="inline-flex items-center justify-center gap-1">ชื่อของรอบงบประมาณ<span class="text-[10px] text-slate-400" data-sort-indicator-for="budget_round_group_name"></span></span></th>
                                <th class="cursor-pointer bg-blue-50 px-3 py-3 text-center hover:bg-blue-100/60" data-sort-key="budget_val"><span class="inline-flex items-center justify-center gap-1">งบประมาณที่ได้รับจัดสรรตามรอบงบประมาณ<span class="text-[10px] text-slate-400" data-sort-indicator-for="budget_val"></span></span></th>
                                <th class="cursor-pointer bg-blue-50 px-3 py-3 text-center hover:bg-blue-100/60" data-sort-key="delta"><span class="inline-flex items-center justify-center gap-1">(+/-)<span class="text-[10px] text-slate-400" data-sort-indicator-for="delta"></span></span></th>
                                <th class="cursor-pointer bg-blue-50 px-3 py-3 text-center hover:bg-blue-100/60" data-sort-key="budget_balance"><span class="inline-flex items-center justify-center gap-1">งบประมาณคงเหลือ<span class="text-[10px] text-slate-400" data-sort-indicator-for="budget_balance"></span></span></th>
                                <th class="w-[132px] px-3 py-3 text-center align-middle">เครื่องมือ</th>
                            </tr>
                        </thead>
                        <tbody id="one-planning-table" class="divide-y divide-slate-100 text-slate-700 [&>tr>td]:align-top"></tbody>
                    </table>
                </div>
                <x-pagination-bar infoId="table-info" pageId="pagination-page" prevId="btn-prev" nextId="btn-next" viewAllId="btn-view-all" viewAllText="แสดงทั้งหมด" />
            </div>
            <x-modal name="one-planning-detail-modal" maxWidth="6xl" variant="modern" title="รายละเอียดงบประมาณ"
                description="วงเงิน โครงสร้างแผน และรายการจ่ายของรหัสนี้" titleId="one-planning-detail-title"
                iconClass="fas fa-file-invoice-dollar">
                <style>
                    #one-planning-detail-body { max-height:68vh; overflow:auto; overscroll-behavior:contain; scrollbar-gutter:stable; padding:2px 6px 8px 2px; }
                    .op-hero { padding:20px; border:1px solid #dbeafe; border-radius:22px; background:linear-gradient(125deg,#eff6ff,#f0fdf4); }
                    .op-metrics { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin:16px 0; }
                    .op-metric { padding:16px; border:1px solid #e2e8f0; border-radius:18px; background:#fff; }
                    .op-metric strong { display:block; margin-top:10px; font-size:clamp(20px,2.2vw,28px); line-height:1.3; font-variant-numeric:tabular-nums; overflow-wrap:anywhere; }
                    .op-metric small { display:block; margin-top:6px; font-size:11px; color:#64748b; }
                    .op-metric-balance { border-color:#a7f3d0; background:#ecfdf5; }
                    .op-section { margin:16px 0; border:1px solid #e2e8f0; border-radius:18px; background:#fff; overflow:hidden; }
                    .op-section-heading { padding:15px 18px; font-size:13px; font-weight:700; color:#334155; background:#f8fafc; }
                    .op-facts { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 24px; padding:0 18px; }
                    .op-fact { padding:13px 0; border-bottom:1px solid #f1f5f9; overflow-wrap:anywhere; }
                    .op-fact dt { font-size:11px; line-height:1.6; color:#64748b; }
                    .op-fact dd { margin:5px 0 0; font-size:13px; font-weight:600; line-height:1.7; color:#1e293b; }
                    .op-line-wrap { overflow-x:auto; border:1px solid #e2e8f0; border-radius:18px; }
                    .op-lines { width:100%; min-width:760px; text-align:left; font-size:13px; }
                    .op-lines th { font-weight:600; line-height:1.7; }
                    .op-lines td:nth-child(2) { min-width:200px; overflow-wrap:anywhere; }
                    .op-lines tbody tr:hover { background:#f0fdf4; }
                    @media(max-width:640px) {
                        #one-planning-detail-body { max-height:65vh; padding-right:2px; }
                        .op-hero { padding:16px; }
                        .op-metrics { grid-template-columns:1fr; gap:8px; }
                        .op-metric { padding:13px 16px; }
                        .op-metric strong { margin-top:5px; font-size:24px; }
                        .op-facts { grid-template-columns:1fr; }
                        .op-lines { min-width:0; }
                        .op-lines thead { display:none; }
                        .op-lines tbody, .op-lines tr, .op-lines td { display:block; }
                        .op-lines tbody tr { padding:12px; border-bottom:6px solid #f1f5f9; }
                        .op-lines tbody td { padding:7px 4px; text-align:left; white-space:normal; background:transparent; }
                        .op-lines tbody td:first-child { display:none; }
                        .op-lines tbody td[data-label]::before { content:attr(data-label); display:block; margin-bottom:4px; font-size:11px; color:#64748b; font-weight:400; }
                        .op-lines tfoot tr { display:flex; flex-wrap:wrap; padding:12px; gap:8px; }
                        .op-lines tfoot td { flex:1 1 40%; padding:4px; text-align:left; }
                        .op-lines tfoot td:first-child { flex-basis:100%; }
                        .op-lines tfoot td:nth-child(2)::before { content:'งบประมาณที่ได้รับจัดสรร'; display:block; font-size:10px; color:#64748b; }
                        .op-lines tfoot td:nth-child(3)::before { content:'ส่วนต่าง'; display:block; font-size:10px; color:#64748b; }
                        .op-lines tfoot td:nth-child(4)::before { content:'งบประมาณคงเหลือ'; display:block; font-size:10px; color:#64748b; }
                    }
                </style>
                <div id="one-planning-detail-body"></div>
            </x-modal>
        @endif
    </div>

    @unless ($error)
        @push('scripts')
            <script>
                (function () {
                    const allRows = @json($rows);
                    const tbody = document.getElementById('one-planning-table');
                    const filterSearch = document.getElementById('filter-search');
                    const btnClearSearch = document.getElementById('btn-clear-search');
                    const filterType = document.getElementById('filter-type');
                    const filterPlan = document.getElementById('filter-plan');
                    const filterFund = document.getElementById('filter-fund');
                    const filterCategory = document.getElementById('filter-category');
                    const filterRound = document.getElementById('filter-round');
                    const btnReset = document.getElementById('btn-reset-filter');
                    const btnPrev = document.getElementById('btn-prev');
                    const btnNext = document.getElementById('btn-next');
                    const btnViewAll = document.getElementById('btn-view-all');
                    const tableInfo = document.getElementById('table-info');
                    const paginationPage = document.getElementById('pagination-page');
                    const sumAmount = document.getElementById('sum-amount');
                    const sumBalance = document.getElementById('sum-balance');
                    const sumDelta = document.getElementById('sum-delta');
                    const sumCount = document.getElementById('sum-count');
                    const pageSize = 10;
                    let currentPage = 1;
                    let viewAll = false;
                    let sortKey = 'code';
                    let sortDir = 'asc';
                    let filteredRows = allRows.slice();

                    const esc = (value) => String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                    const money = (value) => {
                        const n = Number(value || 0);
                        const whole = Number.isFinite(n) && Math.abs(n - Math.round(n)) < 1e-9;
                        return n.toLocaleString('th-TH', { minimumFractionDigits: whole ? 0 : 2, maximumFractionDigits: 2 });
                    };
                    const signedClass = (value) => Number(value || 0) < 0 ? 'text-red-700' : (Number(value || 0) > 0 ? 'text-green-700' : '');

                    const applyFilters = () => {
                        const search = (filterSearch?.value || '').toLowerCase().trim();
                        const type = filterType?.value || '';
                        const plan = filterPlan?.value || '';
                        const fund = filterFund?.value || '';
                        const category = filterCategory?.value || '';
                        const round = filterRound?.value || '';
                        filteredRows = allRows.filter((row) => {
                            if (type && row.budget_type_name !== type) return false;
                            if (plan && row.budget_3d_plan_name !== plan) return false;
                            if (fund && row.budget_fund_name !== fund) return false;
                            if (category && row.budget_expend_group_name !== category) return false;
                            if (round && row.budget_round_group_name !== round) return false;
                            if (!search) return true;
                            return [row.code, row.name, row.source_path, row.pj_project_name, row.budget_fund_name, row.budget_type_name, row.budget_3d_plan_name, row.budget_expend_group_name, row.budget_department_name, row.budget_round_group_name, row.reason_request]
                                .concat((row.items || []).flatMap((item) => [item.budget_item_name, item.reason_request, item.item_unit, item.budget_round_group_name]))
                                .join(' ')
                                .toLowerCase()
                                .includes(search);
                        });
                        filteredRows.sort((a, b) => {
                            const av = a[sortKey];
                            const bv = b[sortKey];
                            const dir = sortDir === 'asc' ? 1 : -1;
                            if (['budget_val', 'budget_balance', 'delta'].includes(sortKey)) return (Number(av || 0) - Number(bv || 0)) * dir;
                            return String(av ?? '').localeCompare(String(bv ?? ''), 'th') * dir;
                        });
                        currentPage = 1;
                        if (btnClearSearch) {
                            btnClearSearch.classList.toggle('hidden', search === '');
                            btnClearSearch.classList.toggle('inline-flex', search !== '');
                        }
                        render();
                    };

                    const render = () => {
                        const total = filteredRows.reduce((sum, row) => sum + Number(row.budget_val || 0), 0);
                        const balance = filteredRows.reduce((sum, row) => sum + Number(row.budget_balance || 0), 0);
                        const delta = filteredRows.reduce((sum, row) => sum + Number(row.delta || 0), 0);
                        if (sumAmount) sumAmount.textContent = money(total);
                        if (sumBalance) sumBalance.textContent = money(balance);
                        if (sumDelta) {
                            sumDelta.textContent = money(delta);
                            sumDelta.className = 'mt-1 text-xl font-bold ' + (signedClass(delta) || 'text-slate-700');
                        }
                        if (sumCount) sumCount.textContent = filteredRows.length.toLocaleString('th-TH') + ' รายการหลัก';

                        const start = viewAll ? 0 : (currentPage - 1) * pageSize;
                        const end = viewAll ? filteredRows.length : start + pageSize;
                        const pageRows = filteredRows.slice(start, end);
                        const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
                        if (pageRows.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">ไม่พบข้อมูล</td></tr>';
                        } else {
                            tbody.innerHTML = pageRows.map((row, index) => {
                                const detail = [row.pj_project_name, row.budget_department_name, row.reason_request].filter(Boolean).join(' · ');
                                const title = row.name || row.source_path || '-';
                                const path = row.source_path && row.source_path !== title ? row.source_path : '';
                                const itemCount = Number(row.item_count || 0);
                                return `<tr class="odd:bg-white even:bg-slate-50/40 transition-colors hover:bg-slate-100/60">
                                    <td class="px-3 py-3 text-center align-top tabular-nums text-slate-500">${start + index + 1}</td>
                                    <td class="px-3 py-3 text-center align-top font-semibold tabular-nums">${esc(row.code) || '-'}</td>
                                    <td class="whitespace-normal break-words px-3 py-3 align-top text-xs text-slate-700" title="${esc(row.source_path)}">
                                        <div class="text-sm font-semibold text-slate-700">${esc(title)}</div>
                                        ${path ? `<div class="mt-1 text-slate-500">${esc(path)}</div>` : ''}
                                        ${detail ? `<div class="mt-1 text-slate-500">${esc(detail)}</div>` : ''}
                                        ${itemCount > 1 ? `<div class="mt-1 font-semibold text-blue-700">${itemCount.toLocaleString('th-TH')} รายการจ่าย</div>` : ''}
                                    </td>
                                    <td class="px-3 py-3 text-center align-top text-sm font-semibold text-slate-700">${esc(row.budget_round_group_name) || '—'}</td>
                                    <td class="bg-blue-50/40 px-3 py-3 text-right align-top tabular-nums">${money(row.budget_val)}</td>
                                    <td class="bg-blue-50/40 px-3 py-3 text-right align-top tabular-nums ${signedClass(row.delta)}">${money(row.delta)}</td>
                                    <td class="bg-blue-50/40 px-3 py-3 text-right align-top font-semibold tabular-nums">${money(row.budget_balance)}</td>
                                    <td class="px-3 py-3 text-center align-top">
                                        <button type="button" data-detail-id="${esc(row.id)}" class="inline-flex items-center justify-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-2 text-xs font-semibold text-blue-700 shadow-sm hover:bg-blue-100">
                                            <i class="fas fa-file-alt text-[10px]"></i>รายละเอียด
                                        </button>
                                    </td>
                                </tr>`;
                            }).join('');
                        }
                        if (tableInfo) {
                            tableInfo.textContent = filteredRows.length > 0
                                ? `แสดง ${start + 1} - ${Math.min(end, filteredRows.length)} จาก ${filteredRows.length} รายการ`
                                : '';
                        }
                        if (paginationPage) paginationPage.textContent = viewAll ? 'แสดงทั้งหมด' : `หน้า ${Math.min(currentPage, totalPages)}/${totalPages}`;
                        if (btnPrev) btnPrev.disabled = viewAll || currentPage <= 1;
                        if (btnNext) btnNext.disabled = viewAll || currentPage >= totalPages;
                        if (btnViewAll) btnViewAll.textContent = viewAll ? 'แสดงน้อยลง' : 'แสดงทั้งหมด';
                        document.querySelectorAll('[data-sort-indicator-for]').forEach((el) => {
                            el.textContent = el.getAttribute('data-sort-indicator-for') === sortKey ? (sortDir === 'asc' ? '▲' : '▼') : '';
                        });
                    };

                    const paintTypeButton = (button, active) => {
                        button.classList.toggle('active', active);
                        button.style.backgroundColor = active ? '#2563eb' : '#ffffff';
                        button.style.color = active ? '#ffffff' : '#2563eb';
                        button.style.borderColor = active ? '#2563eb' : '#93c5fd';
                    };
                    const setHiddenValue = (hiddenId, value) => {
                        const el = document.getElementById(hiddenId);
                        if (!el || el.value === (value ?? '')) return;
                        el.value = value ?? '';
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    };
                    const paintTypeSelection = (value) => {
                        document.querySelectorAll('.type-filter-btn').forEach((item) => paintTypeButton(item, (item.dataset.filterType || '') === value));
                    };
                    document.querySelectorAll('.type-filter-btn').forEach((button) => {
                        paintTypeButton(button, (button.dataset.filterType || '') === '');
                        button.addEventListener('click', () => setHiddenValue('filter-type', button.dataset.filterType || ''));
                    });
                    document.querySelectorAll('[data-sort-key]').forEach((header) => {
                        header.addEventListener('click', () => {
                            const key = header.getAttribute('data-sort-key');
                            if (sortKey === key) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                            else { sortKey = key; sortDir = 'asc'; }
                            applyFilters();
                        });
                    });
                    filterSearch?.addEventListener('input', applyFilters);
                    [filterType, filterPlan, filterFund, filterCategory, filterRound].forEach((input) => {
                        input?.addEventListener('change', () => {
                            paintTypeSelection(filterType?.value || '');
                            applyFilters();
                        });
                    });
                    btnClearSearch?.addEventListener('click', () => { filterSearch.value = ''; applyFilters(); });
                    btnReset?.addEventListener('click', () => {
                        if (filterSearch) filterSearch.value = '';
                        [filterType, filterPlan, filterFund, filterCategory, filterRound].forEach((input) => {
                            if (!input) return;
                            input.value = '';
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        });
                        paintTypeSelection('');
                        sortKey = 'code';
                        sortDir = 'asc';
                        viewAll = false;
                        applyFilters();
                    });
                    const openDetail = (row) => {
                        const title = document.getElementById('one-planning-detail-title');
                        const body = document.getElementById('one-planning-detail-body');
                        if (title) title.textContent = row.code ? `รหัสงบประมาณ ${row.code}` : 'รายละเอียดงบประมาณ';
                        if (!body) return;
                        const factMap = new Map((row.facts || []).map((fact) => [fact.label, fact.value]));
                        const factGroups = [
                            ['ข้อมูลงบ', ['ปีงบประมาณที่ได้รับงบประมาณ (พ.ศ.)', 'ชื่อของประเภทงบประมาณ', 'ชื่อของกองทุน', 'ชื่อของหมวดรายจ่าย', 'ชื่อของรอบงบประมาณในแต่ละปี', 'ชื่อของรอบงบประมาณ']],
                            ['โครงสร้างแผน', ['ชื่อของแผนงานมหาวิทยาลัย', 'ชื่อของงานมหาวิทยาลัย', 'ชื่อของกิจกรรมมหาวิทยาลัย', 'ชื่อของแผนงานที่ใช้ประกอบงบประมาณ', 'ชื่อของแผนงานสำนักงานประมาณ', 'ชื่อของผลผลิตสำนักงานประมาณ', 'ชื่อของกิจกรรมสำนักงานประมาณ']],
                            ['ส่วนงานและยุทธศาสตร์', ['ชื่อของส่วนงานตามโครงสร้างงบประมาณ', 'ชื่อของส่วนงานตามโครงสร้างมหาวิทยาลัย', 'ชื่อของโครงการ/งาน', 'ชื่อของยุทธศาสตร์หลักมหาวิทยาลัย', 'ชื่อของยุทธศาสตร์ย่อยมหาวิทยาลัย']],
                        ];
                        const usedLabels = new Set(factGroups.flatMap((group) => group[1]));
                        const extraLabels = [...factMap.keys()].filter((label) => label !== 'ชื่อของงบประมาณ' && !usedLabels.has(label));
                        if (extraLabels.length) factGroups.push(['ข้อมูลอื่น', extraLabels]);
                        const factRow = (label) => {
                            if (!factMap.has(label)) return '';
                            return `<div class="op-fact"><dt>${esc(label)}</dt><dd>${esc(factMap.get(label))}</dd></div>`;
                        };
                        const factSections = factGroups.map(([heading, labels]) => {
                            const rows = labels.map(factRow).join('');
                            if (!rows) return '';
                            return `<section class="op-section">
                                <h4 class="op-section-heading">${esc(heading)} <span class="ml-2 text-xs font-normal text-slate-400">${labels.filter((label) => factMap.has(label)).length} ข้อมูล</span></h4>
                                <dl class="op-facts">${rows}</dl>
                            </section>`;
                        }).join('');
                        const lines = (row.items || []).slice().sort((a, b) => Number(b.budget_balance || 0) - Number(a.budget_balance || 0) || Number(b.budget_val || 0) - Number(a.budget_val || 0));
                        const percent = (part, total) => {
                            const base = Number(total || 0);
                            if (!base) return 0;
                            return (Number(part || 0) / base) * 100;
                        };
                        const percentText = (value) => Number(value || 0).toLocaleString('th-TH', { maximumFractionDigits: 1 }) + '%';
                        const parentAmount = Number(row.budget_val || 0);
                        const parentBalance = Number(row.budget_balance || 0);
                        const heading = row.name || 'รายละเอียดงบประมาณ';
                        const path = row.source_path !== row.name ? (row.source_path || '').split('/').filter(Boolean).join(' / ') : '';
                        let remainBar = '';
                        if (parentAmount > 0) {
                            const ratio = Math.max(0, Math.min(100, (parentBalance / parentAmount) * 100));
                            const over = parentBalance > parentAmount;
                            remainBar = `<div class="mb-5 rounded-2xl border border-emerald-100 bg-white p-4">
                                <div role="img" aria-label="${esc('สัดส่วนงบประมาณคงเหลือ ' + percentText((parentBalance / parentAmount) * 100))}" style="height:8px;border-radius:999px;background:#e2e8f0;overflow:hidden">
                                    <div style="height:8px;width:${over ? 100 : ratio}%;background:${over ? '#2563eb' : '#059669'}"></div>
                                </div>
                                <div class="mt-1 text-xs font-semibold text-slate-500">${over ? 'งบประมาณคงเหลือสูงกว่างบประมาณที่ได้รับจัดสรร' : 'คงเหลือ ' + percentText(ratio) + ' ของงบประมาณที่ได้รับจัดสรรตามรอบงบประมาณ'}</div>
                            </div>`;
                        }
                        const lineRow = (item, index) => {
                            const amount = Number(item.budget_val || 0);
                            const balance = Number(item.budget_balance || 0);
                            const delta = balance - amount;
                            const unitPrice = Number(String(item.item_ppu || '').replace(/,/g, ''));
                            const quantityText = [item.item_amount, item.item_unit].filter(Boolean).join(' ');
                            const remainText = amount > 0 ? `คงเหลือ ${percentText(Math.max(0, Math.min(100, (balance / amount) * 100)))}` : '';
                            const shareText = lines.length > 1 && parentAmount > 0 ? `${percentText(percent(amount, parentAmount))} ของรหัสนี้` : '';
                            return `<tr class="border-b border-slate-100 odd:bg-white even:bg-slate-50/40">
                                <td class="px-3 py-3 text-center align-top tabular-nums text-slate-500">${index + 1}</td>
                                <td data-label="ชื่อของรายการจ่าย" class="px-3 py-3 align-top">
                                    <div class="font-semibold text-slate-700">${esc(item.budget_item_name) || 'ไม่ระบุชื่อของรายการจ่าย'}${item.budget_round_group_name ? ` <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">${esc(item.budget_round_group_name)}</span>` : ''}</div>
                                    ${item.reason_request ? `<div class="mt-2 text-xs leading-relaxed text-slate-500"><span class="font-semibold">เหตุผลในการขอตั้ง:</span> ${esc(item.reason_request)}</div>` : ''}
                                </td>
                                <td data-label="จำนวนและหน่วยนับ · ราคาต่อหน่วย" class="whitespace-nowrap px-3 py-3 align-top text-slate-700">
                                    <div>${quantityText ? esc(quantityText) : '—'}</div>
                                    ${unitPrice > 0 ? `<div class="mt-1 text-xs text-slate-500">${money(unitPrice)} /หน่วย</div>` : ''}
                                </td>
                                <td data-label="งบประมาณที่ได้รับจัดสรรตามรอบงบประมาณ" class="whitespace-nowrap bg-blue-50/40 px-3 py-3 text-right align-top tabular-nums">
                                    <div class="font-semibold">${money(amount)}</div>
                                    ${shareText ? `<div class="mt-1 text-xs text-slate-500">${shareText}</div>` : ''}
                                </td>
                                <td data-label="ส่วนต่าง" class="whitespace-nowrap bg-blue-50/40 px-3 py-3 text-right align-top font-semibold tabular-nums ${signedClass(delta)}">${money(delta)}</td>
                                <td data-label="งบประมาณคงเหลือ" class="whitespace-nowrap bg-blue-50/40 px-3 py-3 text-right align-top tabular-nums">
                                    <div class="font-semibold">${money(balance)}</div>
                                    ${remainText ? `<div class="mt-1 text-xs text-slate-500">${remainText}</div>` : ''}
                                </td>
                            </tr>`;
                        };
                        const totals = (shown) => {
                            const totalAmount = shown.reduce((sum, item) => sum + Number(item.budget_val || 0), 0);
                            const totalBalance = shown.reduce((sum, item) => sum + Number(item.budget_balance || 0), 0);
                            return { totalAmount, totalBalance, totalDelta: totalBalance - totalAmount };
                        };
                        const paintLines = (query) => {
                            const keyword = query.trim().toLowerCase();
                            const shown = keyword
                                ? lines.filter((item) => [item.budget_item_name, item.reason_request, item.item_unit, item.budget_round_group_name].join(' ').toLowerCase().includes(keyword))
                                : lines;
                            const lineBody = document.getElementById('one-planning-line-body');
                            const lineFoot = document.getElementById('one-planning-line-foot');
                            const lineCount = document.getElementById('one-planning-line-count');
                            if (lineCount) {
                                lineCount.textContent = keyword
                                    ? `แสดง ${shown.length.toLocaleString('th-TH')} จาก ${lines.length.toLocaleString('th-TH')} รายการ`
                                    : `${lines.length.toLocaleString('th-TH')} รายการ · เรียงตามงบประมาณคงเหลือ`;
                            }
                            if (lineBody) {
                                lineBody.innerHTML = shown.length
                                    ? shown.map(lineRow).join('')
                                    : `<tr><td colspan="6" class="px-3 py-6 text-center text-slate-400">${keyword ? 'ไม่พบรายการจ่ายที่ค้นหา' : 'ไม่มีรายการจ่าย'}</td></tr>`;
                            }
                            if (lineFoot) {
                                if (!shown.length) {
                                    lineFoot.innerHTML = '';
                                    return;
                                }
                                const sum = totals(shown);
                                lineFoot.innerHTML = `<tr class="border-t border-slate-200 bg-slate-50 text-slate-800">
                                    <td colspan="3" class="px-3 py-3 text-right text-xs font-semibold">${keyword ? 'รวมที่แสดง' : 'รวม'}</td>
                                    <td class="px-3 py-3 text-right font-semibold tabular-nums">${money(sum.totalAmount)}</td>
                                    <td class="px-3 py-3 text-right font-semibold tabular-nums ${signedClass(sum.totalDelta)}">${money(sum.totalDelta)}</td>
                                    <td class="px-3 py-3 text-right font-semibold tabular-nums">${money(sum.totalBalance)}</td>
                                </tr>`;
                            }
                        };
                        body.innerHTML = `
                            <div class="op-hero">
                                <div class="mb-2 flex flex-wrap items-center gap-2 text-xs font-semibold text-emerald-700">
                                    <span class="rounded-full border border-emerald-200 bg-white px-3 py-1">One Planning</span>
                                    <span class="text-slate-500">หน่วย: บาท</span>
                                </div>
                                <h4 class="break-words text-lg font-black leading-relaxed text-slate-900">${esc(heading)}</h4>
                                ${path ? `<p class="mt-2 break-words text-xs leading-relaxed text-slate-500">${esc(path)}</p>` : ''}
                            </div>
                            <div class="op-metrics">
                                <div class="op-metric">
                                    <div class="text-xs font-semibold leading-relaxed text-slate-500">งบประมาณที่ได้รับจัดสรรตามรอบงบประมาณ</div>
                                    <strong class="text-blue-600">${money(parentAmount)}</strong>
                                    <small>จำนวนงบประมาณที่ได้รับจัดสรร</small>
                                </div>
                                <div class="op-metric op-metric-balance">
                                    <div class="text-xs font-semibold leading-relaxed text-emerald-700">งบประมาณคงเหลือ</div>
                                    <strong class="text-emerald-700">${money(parentBalance)}</strong>
                                    <small>ณ วันเวลาเรียกข้อมูลจาก One Planning</small>
                                </div>
                                <div class="op-metric">
                                    <div class="text-xs font-semibold leading-relaxed text-slate-500">ส่วนต่าง</div>
                                    <strong class="${signedClass(row.delta) || 'text-slate-700'}">${money(row.delta)}</strong>
                                    <small>งบประมาณคงเหลือ − งบประมาณที่ได้รับจัดสรร</small>
                                </div>
                            </div>
                            ${remainBar}
                            ${factSections}
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <h4 class="text-sm font-black text-slate-700">รายการจ่าย</h4>
                                <span id="one-planning-line-count" role="status" aria-live="polite" class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"></span>
                            </div>
                            ${lines.length > 1 ? `<div class="mb-3">
                                <label for="one-planning-line-search" class="sr-only">ค้นหารายการจ่าย</label>
                                <input id="one-planning-line-search" type="text" placeholder="ค้นหาในรายการจ่าย..." class="w-full rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700 shadow-sm placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-100" />
                            </div>` : ''}
                            <div class="op-line-wrap">
                                <table class="op-lines">
                                    <caption class="sr-only">รายการจ่ายของงบประมาณ ${esc(row.code)} หน่วยบาท</caption>
                                    <thead class="sticky top-0 z-10">
                                        <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-600">
                                            <th class="w-11 px-3 py-2 text-center">#</th>
                                            <th class="px-3 py-2">ชื่อของรายการจ่าย</th>
                                            <th class="px-3 py-2">จำนวนและหน่วยนับของรายการจ่าย</th>
                                            <th class="bg-blue-50 px-3 py-2 text-right">งบประมาณที่ได้รับจัดสรรตามรอบงบประมาณ</th>
                                            <th class="bg-blue-50 px-3 py-2 text-right">ส่วนต่าง</th>
                                            <th class="bg-blue-50 px-3 py-2 text-right">งบประมาณคงเหลือ</th>
                                        </tr>
                                    </thead>
                                    <tbody id="one-planning-line-body">${lines.length ? '' : '<tr><td colspan="6" class="px-3 py-6 text-center text-slate-400">ไม่มีรายการจ่าย</td></tr>'}</tbody>
                                    <tfoot id="one-planning-line-foot"></tfoot>
                                </table>
                            </div>`;
                        body.scrollTop = 0;
                        paintLines('');
                        document.getElementById('one-planning-line-search')?.addEventListener('input', (event) => paintLines(event.target.value || ''));
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'one-planning-detail-modal' }));
                    };
                    tbody?.addEventListener('click', (event) => {
                        const button = event.target.closest('[data-detail-id]');
                        if (!button) return;
                        const row = allRows.find((item) => item.id === button.dataset.detailId);
                        if (row) openDetail(row);
                    });
                    btnPrev?.addEventListener('click', () => { if (currentPage > 1) { currentPage -= 1; render(); } });
                    btnNext?.addEventListener('click', () => {
                        const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
                        if (currentPage < totalPages) { currentPage += 1; render(); }
                    });
                    btnViewAll?.addEventListener('click', () => { viewAll = !viewAll; currentPage = 1; render(); });
                    applyFilters();
                })();
            </script>
        @endpush
    @endunless
</x-layouts.app>
