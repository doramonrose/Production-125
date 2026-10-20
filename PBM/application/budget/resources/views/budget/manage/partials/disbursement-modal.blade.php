<x-modal name="budget-disbursement-modal" maxWidth="3xl" variant="modern" title="รายการเบิกจ่าย"
    description="รายการจากระบบเบิกจ่ายที่รวมเป็นยอดนี้"
    titleId="budget-disbursement-modal-title" iconClass="fas fa-file-invoice-dollar">
    <div id="budget-disbursement-meta" class="mb-4"></div>
    <div id="budget-disbursement-body" class="max-h-[min(58vh,520px)] overflow-y-auto pr-1">
        <div class="px-4 py-10 text-center text-sm text-slate-400">กำลังโหลด...</div>
    </div>
    <div id="budget-disbursement-total" class="hidden mt-3"></div>
    <div class="mt-4 flex justify-end">
        <button type="button" @click="$dispatch('close-modal', 'budget-disbursement-modal')"
            class="inline-flex h-10 items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 text-sm font-extrabold text-slate-600 shadow-sm transition hover:bg-slate-50">
            ปิด
        </button>
    </div>
</x-modal>

@once
    @push('scripts')
        <script>
            (function () {
                const urlTpl = @json(route('budget.api.manage.disbursements', ['budgetManageId' => '__ID__']));
                const titleEl = () => document.getElementById('budget-disbursement-modal-title');
                const metaEl = () => document.getElementById('budget-disbursement-meta');
                const bodyEl = () => document.getElementById('budget-disbursement-body');
                const totalEl = () => document.getElementById('budget-disbursement-total');
                const money = (v) => {
                    const n = Number(v || 0);
                    const isWhole = Number.isFinite(n) && Math.abs(n - Math.round(n)) < 1e-9;
                    return n.toLocaleString(undefined, {
                        minimumFractionDigits: isWhole ? 0 : 2,
                        maximumFractionDigits: 2
                    });
                };
                const moneyFixed = (v) => Number(v || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                const thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                const parseThaiDate = (value) => {
                    const match = String(value || '').trim().match(/^(\d{1,2})\s+(\S+)\s+(\d{2,4})$/);
                    if (!match) return null;
                    const month = thaiMonths.indexOf(match[2]);
                    if (month < 0) return null;
                    let year = Number(match[3]);
                    if (year < 100) year += 2500;
                    return {
                        day: Number(match[1]),
                        month: month + 1,
                        year,
                        monthLabel: match[2],
                    };
                };
                const shortThaiDate = (parsed) => {
                    if (!parsed) return '-';
                    return `${parsed.day} ${parsed.monthLabel} ${String(parsed.year).slice(-2)}`;
                };
                const withdrawTypeLabel = (source) => ({
                    salary: 'เบิกเงินเดือน',
                    general: 'เบิกทั่วไป',
                    project: 'เบิกโครงการ',
                }[String(source || '').toLowerCase()] || '');
                const esc = (v) => String(v ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');

                const itemLabel = (item) => {
                    const category = String(item.item_category || '').trim();
                    const itemName = String(item.item_name || '').trim();
                    const parts = [];
                    if (category && !/^\d+$/.test(category)) parts.push(category);
                    if (itemName) parts.push(itemName);
                    return parts.join(' · ') || 'รายการเบิกจ่าย';
                };

                const groupByDocument = (items) => {
                    const groups = [];
                    const indexByKey = {};
                    items.forEach((item) => {
                        const key = `${item.source || ''}:${item.document_id || 0}:${item.document_number || ''}`;
                        if (indexByKey[key] == null) {
                            indexByKey[key] = groups.length;
                            groups.push({
                                source: item.source || '',
                                source_label: item.source_label || '-',
                                document_id: item.document_id || 0,
                                document_number: item.document_number || '',
                                subject: item.subject || '',
                                applicant_name: item.applicant_name || '',
                                request_date: item.request_date || '',
                                approved_date: item.approved_date || '',
                                date: item.date || '',
                                status: item.status || '',
                                status_label: item.status_label || '-',
                                amount: 0,
                                items: [],
                            });
                        }
                        const group = groups[indexByKey[key]];
                        group.items.push(item);
                        group.amount += Number(item.amount || 0);
                    });
                    return groups;
                };

                const isPendingStatus = (status, kind) => {
                    const value = String(status || '').toLowerCase();
                    return kind === 'pending' || value === 'processing' || value.startsWith('pending');
                };

                const statusTone = (kind, status) => {
                    if (isPendingStatus(status, kind)) {
                        return {
                            card: 'border-orange-200/80 bg-gradient-to-br from-orange-50/90 via-white to-amber-50/40',
                            badge: 'border-orange-200 bg-orange-50 text-orange-700',
                            amount: 'text-orange-700',
                            icon: 'fa-clock',
                        };
                    }
                    return {
                        card: 'border-emerald-200/80 bg-gradient-to-br from-emerald-50/90 via-white to-blue-50/30',
                        badge: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                        amount: 'text-emerald-700',
                        icon: 'fa-circle-check',
                    };
                };

                const displayStatusLabel = (group, kind) => {
                    if (isPendingStatus(group.status, kind)) return 'รอเบิกจ่าย';
                    return group.status_label || 'อนุมัติ';
                };

                const resetModal = (title) => {
                    if (titleEl()) titleEl().textContent = title || 'รายการเบิกจ่าย';
                    if (metaEl()) metaEl().innerHTML = '';
                    if (totalEl()) {
                        totalEl().classList.add('hidden');
                        totalEl().innerHTML = '';
                    }
                    if (bodyEl()) {
                        bodyEl().innerHTML = '<div class="px-4 py-10 text-center text-sm text-slate-400">กำลังโหลด...</div>';
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', {
                        detail: 'budget-disbursement-modal'
                    }));
                };

                const renderGroupCard = (group, kind) => {
                    const tone = statusTone(kind, group.status);
                    const docNo = group.document_number || '-';
                    const items = Array.isArray(group.items) ? group.items : [];
                    return `
                        <article class="overflow-hidden rounded-3xl border ${tone.card} shadow-[0_10px_24px_-18px_rgba(15,23,42,0.35)]">
                            <div class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white/80 px-2.5 py-1 text-[11px] font-extrabold text-slate-600">
                                        <i class="fas fa-file-invoice text-[10px] text-slate-400"></i>
                                        ${esc(group.source_label || '-')}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border ${tone.badge} px-2.5 py-1 text-[11px] font-extrabold">
                                        <i class="fas ${tone.icon} text-[10px]"></i>
                                        ${esc(displayStatusLabel(group, kind))}
                                    </span>
                                </div>
                                <h4 class="mt-2 text-sm font-black leading-snug text-slate-900">${esc(group.subject || 'ไม่มีชื่อเรื่อง')}</h4>
                                <div class="mt-1.5 flex flex-col gap-1 text-[11px] font-semibold text-slate-500">
                                    <span class="inline-flex items-start gap-1.5 leading-snug break-all">
                                        <i class="fas fa-hashtag mt-0.5 w-3.5 text-center text-slate-400"></i>
                                        <span><span class="text-slate-400">เลขที่หนังสือ</span> ${esc(docNo)}</span>
                                    </span>
                                    <span class="inline-flex items-start gap-1.5 leading-snug">
                                        <i class="fas fa-user mt-0.5 w-3.5 text-center text-slate-400"></i>
                                        <span><span class="text-slate-400">ผู้ขอเบิกจ่าย</span> ${esc(group.applicant_name || '-')}</span>
                                    </span>
                                    <span class="inline-flex items-start gap-1.5 leading-snug">
                                        <i class="fas fa-calendar-plus mt-0.5 w-3.5 text-center text-slate-400"></i>
                                        <span><span class="text-slate-400">วันที่ขอเบิกจ่าย</span> ${esc(group.request_date || '-')}</span>
                                    </span>
                                    <span class="inline-flex items-start gap-1.5 leading-snug">
                                        <i class="fas fa-calendar-check mt-0.5 w-3.5 text-center text-slate-400"></i>
                                        <span><span class="text-slate-400">วันที่อนุมัติเบิกจ่าย</span> ${esc(group.approved_date || '-')}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="border-t border-white/80 bg-white/55 px-3 py-2.5">
                                <div class="space-y-1.5">
                                    ${items.map((item) => `
                                        <div class="flex items-start justify-between gap-3 rounded-2xl bg-white/90 px-3 py-2 ring-1 ring-slate-200/70">
                                            <div class="min-w-0 text-sm font-semibold text-slate-700">${esc(itemLabel(item))}</div>
                                            <div class="shrink-0 text-sm font-black tabular-nums text-slate-900">${money(item.amount)}</div>
                                        </div>
                                    `).join('')}
                                </div>
                                <div class="mt-2 flex items-center justify-between gap-3 rounded-2xl bg-white px-3 py-2.5 ring-1 ring-slate-200/80">
                                    <div class="text-xs font-extrabold uppercase tracking-wider text-slate-500">รวม</div>
                                    <div class="text-base font-black tabular-nums ${tone.amount}">${money(group.amount)} <span class="text-xs font-bold text-slate-400">บาท</span></div>
                                </div>
                            </div>
                        </article>
                    `;
                };

                const pendingDetailText = (item) => {
                    const typeText = String(item.source_label || '').trim() || withdrawTypeLabel(item.source);
                    const detail = String(item.item_name || item.subject || '').trim();
                    const badge = typeText
                        ? `<span class="inline-flex shrink-0 items-center rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-bold text-sky-700 ring-1 ring-sky-100">${esc(typeText)}</span>`
                        : '';
                    const name = detail
                        ? `<span class="min-w-0 leading-snug text-slate-700">${esc(detail)}</span>`
                        : '<span class="text-slate-400">-</span>';
                    return `<div class="flex flex-wrap items-center gap-2">${badge}${name}</div>`;
                };

                const flattenDisbursementItems = (groups) => {
                    const items = [];
                    (Array.isArray(groups) ? groups : []).forEach((group) => {
                        const lines = Array.isArray(group.items) && group.items.length ? group.items : [group];
                        lines.forEach((item) => {
                            items.push({
                                ...item,
                                source: item.source || group.source || '',
                            });
                        });
                    });
                    return items;
                };

                const renderPendingMonthTable = (groups, total) => {
                    const buckets = new Map();
                    flattenDisbursementItems(groups).forEach((item) => {
                        const rawDate = item.approved_date || item.request_date || item.date || '';
                        const parsed = parseThaiDate(rawDate);
                        const key = parsed ? `${parsed.year}-${String(parsed.month).padStart(2, '0')}` : 'unknown';
                        if (!buckets.has(key)) {
                            buckets.set(key, {
                                key,
                                label: parsed ? parsed.monthLabel : 'ไม่ระบุเดือน',
                                sort: parsed ? parsed.year * 100 + parsed.month : 999999,
                                amount: 0,
                                rows: [],
                            });
                        }
                        const bucket = buckets.get(key);
                        bucket.amount += Number(item.amount || 0);
                        bucket.rows.push({ item, parsed });
                    });

                    const months = [...buckets.values()].sort((a, b) => a.sort - b.sort);
                    months.forEach((month) => {
                        month.rows.sort((a, b) => {
                            const aKey = a.parsed ? a.parsed.year * 10000 + a.parsed.month * 100 + a.parsed.day : 0;
                            const bKey = b.parsed ? b.parsed.year * 10000 + b.parsed.month * 100 + b.parsed.day : 0;
                            return aKey - bKey;
                        });
                    });

                    const today = new Date();
                    const currentYear = today.getFullYear() + 543;
                    const currentMonthKey = `${currentYear}-${String(today.getMonth() + 1).padStart(2, '0')}`;

                    const body = months.map((month) => {
                        const open = month.key === currentMonthKey;
                        const isCurrent = open;
                        const yearSuffix = month.rows[0]?.parsed && month.rows[0].parsed.year !== currentYear
                            ? ` ${String(month.rows[0].parsed.year).slice(-2)}`
                            : '';
                        const monthTitle = `${month.label}${yearSuffix}`;
                        const countLabel = `${month.rows.length} รายการ`;
                        const children = month.rows.map(({ item, parsed }, index) => `
                            <tr class="border-b border-slate-100 text-slate-700 ${index % 2 === 0 ? 'bg-slate-50/70' : 'bg-white'} ${open ? '' : 'hidden'}" data-pending-month="${esc(month.key)}">
                                <td class="whitespace-nowrap px-4 py-3 pl-14 text-slate-500">${esc(shortThaiDate(parsed))}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-slate-800">${moneyFixed(item.amount)}</td>
                                <td class="px-4 py-3">${pendingDetailText(item)}</td>
                            </tr>
                        `).join('');
                        return `
                            <tr class="js-pending-month-toggle cursor-pointer border-b border-slate-200 ${isCurrent ? 'bg-sky-50/80' : 'bg-white'} transition hover:bg-sky-50" data-month="${esc(month.key)}" data-month-label="${esc(monthTitle)}" aria-expanded="${open ? 'true' : 'false'}">
                                <td class="px-4 py-3" colspan="3">
                                    <div class="flex items-center gap-3">
                                        <button type="button" class="inline-flex min-w-0 flex-1 items-center gap-3 text-left" aria-label="${open ? 'ซ่อน' : 'แสดง'}รายการเดือน${esc(monthTitle)}">
                                            <span class="js-pending-month-caret inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${open ? 'rotate-90 bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-500'} transition" aria-hidden="true">
                                                <i class="fas fa-chevron-right text-[10px]"></i>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="flex flex-wrap items-center gap-2">
                                                    <span class="text-sm font-bold text-slate-900">${esc(monthTitle)}</span>
                                                    ${isCurrent ? '<span class="rounded-full bg-sky-600 px-2 py-0.5 text-[10px] font-bold text-white">เดือนนี้</span>' : ''}
                                                </span>
                                                <span class="js-pending-month-hint mt-0.5 block text-[11px] font-medium text-slate-400" data-count="${esc(countLabel)}">${esc(countLabel)} · กดเพื่อ${open ? 'ซ่อน' : 'แสดง'}</span>
                                            </span>
                                        </button>
                                        <div class="shrink-0 text-right">
                                            <div class="text-sm font-bold tabular-nums text-slate-900">${moneyFixed(month.amount)}</div>
                                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">บาท</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            ${children}
                        `;
                    }).join('');

                    return `
                        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        <th class="px-4 py-3 font-bold whitespace-nowrap">วันที่อนุมัติเบิกจ่าย</th>
                                        <th class="px-4 py-3 text-right font-bold whitespace-nowrap">จำนวน (บาท)</th>
                                        <th class="px-4 py-3 font-bold">รายละเอียดการเบิกจ่าย</th>
                                    </tr>
                                </thead>
                                <tbody>${body}</tbody>
                                <tfoot>
                                    <tr class="bg-slate-900 text-white">
                                        <td class="px-4 py-3.5 text-sm font-semibold">รวมเป็นเงิน</td>
                                        <td class="px-4 py-3.5 text-right text-base font-bold tabular-nums">${moneyFixed(total)}</td>
                                        <td class="px-4 py-3.5 text-right text-[11px] font-medium text-slate-300">บาท</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                };

                const fillDisbursementModal = ({ title, groups, total, code, itemCount, kind, showMeta = true }) => {
                    const list = Array.isArray(groups) ? groups : [];
                    if (titleEl()) titleEl().textContent = title || 'รายการเบิกจ่าย';

                    if (metaEl()) {
                        metaEl().innerHTML = showMeta ? `
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2.5">
                                    <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">รหัสงบ</div>
                                    <div class="mt-0.5 truncate text-sm font-black tabular-nums text-slate-800">${esc(code || '-')}</div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2.5">
                                    <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">จำนวนเอกสาร</div>
                                    <div class="mt-0.5 text-sm font-black text-slate-800">${list.length} ฉบับ · ${Number(itemCount || 0)} รายการ</div>
                                </div>
                            </div>
                        ` : '';
                    }

                    if (!bodyEl()) return;
                    if (list.length === 0) {
                        bodyEl().innerHTML = `
                            <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-12 text-center">
                                <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <p class="text-sm font-semibold text-slate-500">ไม่พบรายการ</p>
                            </div>
                        `;
                        if (totalEl()) {
                            totalEl().classList.add('hidden');
                            totalEl().innerHTML = '';
                        }
                        return;
                    }

                    if (showMeta && (kind === 'pending' || kind === 'approved')) {
                        bodyEl().innerHTML = renderPendingMonthTable(list, total);
                        if (totalEl()) {
                            totalEl().classList.add('hidden');
                            totalEl().innerHTML = '';
                        }
                        return;
                    }

                    bodyEl().innerHTML = `<div class="space-y-3">${list.map((group) => renderGroupCard(group, kind)).join('')}</div>`;

                    if (totalEl()) {
                        totalEl().classList.remove('hidden');
                        totalEl().innerHTML = `
                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                                <div class="text-sm font-extrabold text-slate-700">รวมทั้งหมด</div>
                                <div class="text-lg font-black tabular-nums text-amber-800">${money(total)} <span class="text-xs font-bold text-amber-600">บาท</span></div>
                            </div>
                        `;
                    }
                };

                window.openBudgetDisbursementDetail = function (group) {
                    if (!group || typeof group !== 'object') return;
                    const kind = isPendingStatus(group.status) ? 'pending' : 'approved';
                    const items = Array.isArray(group.items) ? group.items : [];
                    resetModal('รายละเอียดรายการเบิกจ่าย');
                    fillDisbursementModal({
                        title: 'รายละเอียดรายการเบิกจ่าย',
                        groups: [group],
                        total: Number(group.amount || 0),
                        code: '',
                        itemCount: items.length,
                        kind,
                        showMeta: false,
                    });
                };

                window.openBudgetDisbursementPopup = async function (manageId, kind) {
                    const id = Number(manageId || 0);
                    const mode = kind === 'pending' ? 'pending' : 'approved';
                    const kindLabel = mode === 'pending' ? 'รอเบิกจ่าย' : 'เบิกจ่าย';
                    if (id <= 0) return;

                    resetModal('รายการ' + kindLabel);

                    try {
                        const url = urlTpl.replace('__ID__', encodeURIComponent(String(id))) + '?kind=' + encodeURIComponent(mode);
                        const res = await fetch(url, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const json = await res.json();
                        const data = json?.data || {};
                        const items = Array.isArray(data.items) ? data.items : [];
                        fillDisbursementModal({
                            title: 'รายการ' + kindLabel,
                            groups: groupByDocument(items),
                            total: Number(data.total || 0),
                            code: String(data.budget_code || ''),
                            itemCount: items.length,
                            kind: mode,
                            showMeta: true,
                        });
                    } catch (e) {
                        if (bodyEl()) {
                            bodyEl().innerHTML = '<div class="px-4 py-10 text-center text-sm text-red-500">โหลดรายการไม่สำเร็จ</div>';
                        }
                    }
                };

                document.addEventListener('click', (e) => {
                    const monthToggle = e.target.closest?.('.js-pending-month-toggle');
                    if (!monthToggle) return;
                    e.preventDefault();
                    e.stopPropagation();
                    const key = monthToggle.getAttribute('data-month') || '';
                    const expanded = monthToggle.getAttribute('aria-expanded') !== 'false';
                    const nextHidden = expanded;
                    const open = !expanded;
                    monthToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    monthToggle.classList.toggle('bg-sky-50/80', open);
                    monthToggle.classList.toggle('bg-white', !open);
                    const caret = monthToggle.querySelector('.js-pending-month-caret');
                    if (caret) {
                        caret.classList.toggle('rotate-90', open);
                        caret.classList.toggle('bg-sky-100', open);
                        caret.classList.toggle('text-sky-700', open);
                        caret.classList.toggle('bg-slate-100', !open);
                        caret.classList.toggle('text-slate-500', !open);
                    }
                    const label = monthToggle.querySelector('button');
                    const monthName = monthToggle.getAttribute('data-month-label') || '';
                    if (label) label.setAttribute('aria-label', `${open ? 'ซ่อน' : 'แสดง'}รายการเดือน${monthName}`);
                    const hint = monthToggle.querySelector('.js-pending-month-hint');
                    if (hint) {
                        const count = hint.getAttribute('data-count') || '';
                        hint.textContent = `${count} · กดเพื่อ${open ? 'ซ่อน' : 'แสดง'}`;
                    }
                    monthToggle.parentElement?.querySelectorAll('[data-pending-month]').forEach((row) => {
                        if (row.getAttribute('data-pending-month') !== key) return;
                        row.classList.toggle('hidden', nextHidden);
                    });
                }, true);

                document.addEventListener('click', (e) => {
                    const detailBtn = e.target.closest?.('.js-disbursement-detail');
                    if (detailBtn) {
                        e.preventDefault();
                        let group = {};
                        try {
                            group = JSON.parse(detailBtn.getAttribute('data-group') || '{}');
                        } catch (err) {
                            group = {};
                        }
                        window.openBudgetDisbursementDetail(group);
                        return;
                    }
                    const btn = e.target.closest?.('.js-disbursement-popup');
                    if (!btn) return;
                    e.preventDefault();
                    window.openBudgetDisbursementPopup(btn.dataset.id, btn.dataset.kind);
                });
            })();
        </script>
    @endpush
@endonce
