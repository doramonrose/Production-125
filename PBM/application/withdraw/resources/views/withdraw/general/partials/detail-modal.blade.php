@include('withdraw.partials.document-preview-scripts')
<x-modal name="withdraw-general-detail-modal" maxWidth="6xl" :static="true" variant="modern"
    class="withdraw-detail-overlay" dialogClass="withdraw-detail-dialog"
    title="รายละเอียดรายการเบิกจ่าย" description="แสดงรายละเอียดทั้งหมดของรายการที่เลือก"
    iconClass="fas fa-circle-info">
    <div class="withdraw-detail-sheet space-y-3 text-sm text-slate-700">
        <div class="p-1">
            <div class="wd-heading mb-2 flex items-start justify-between">
                <div class="text-base font-bold text-slate-900">บันทึกข้อความ</div>
                <div class="wd-status text-sm font-semibold text-rose-600">สถานะ: <span id="withdraw-detail-status">-</span></div>
            </div>
            <div class="wd-fields space-y-1">
                <div class="wd-field flex items-center gap-2">
                    <div class="wd-label w-[76px] shrink-0 font-semibold">ส่วนงาน</div>
                    <div id="withdraw-detail-department" class="wd-value min-w-0 flex-1 px-1 py-1">-</div>
                </div>
                <div class="wd-field-row flex items-center gap-2">
                    <div class="wd-field flex min-w-0 flex-1 items-center gap-2">
                        <div class="wd-label w-[76px] shrink-0 font-semibold">ที่</div>
                        <div id="withdraw-detail-code" class="wd-value min-w-0 flex-1 px-1 py-1">-</div>
                    </div>
                    <div class="wd-field flex shrink-0 items-center gap-2">
                        <div class="wd-label w-[44px] shrink-0 text-right font-semibold">วันที่</div>
                        <div id="withdraw-detail-date" class="wd-value w-[180px] shrink-0 px-1 py-1">-</div>
                    </div>
                </div>
                <div class="wd-field grid grid-cols-[76px_1fr] items-center gap-2">
                    <div class="wd-label font-semibold">เรื่อง</div>
                    <div id="withdraw-detail-subject" class="wd-value px-1 py-1">-</div>
                </div>
                <div class="wd-field grid grid-cols-[76px_1fr] items-center gap-2">
                    <div class="wd-label font-semibold">เรียน</div>
                    <div id="withdraw-detail-to" class="wd-value px-1 py-1">-</div>
                </div>
                <div id="withdraw-detail-memo-body" class="space-y-1">
                    <div class="wd-field grid grid-cols-[76px_1fr] items-start gap-2" data-detail-memo-block="paragraph1">
                        <div class="wd-label-spacer pt-1 font-semibold"></div>
                        <div id="withdraw-detail-paragraph1" class="wd-value min-h-12 px-1 py-1 whitespace-pre-wrap">-</div>
                    </div>
                    <div class="wd-field grid grid-cols-[76px_1fr] items-start gap-2" data-detail-memo-block="paragraph2">
                        <div class="wd-label-spacer pt-1 font-semibold"></div>
                        <div id="withdraw-detail-paragraph2" class="wd-value min-h-12 px-1 py-1 whitespace-pre-wrap">-</div>
                    </div>
                    <div class="py-2" data-detail-memo-block="items">
                        <div class="wd-table-wrap overflow-x-auto">
                            <table class="wd-table min-w-[640px] w-full border-collapse border border-slate-300 text-sm">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="border border-slate-300 px-2 py-1 text-center max-lg:w-10 max-lg:px-1.5 max-lg:py-1.5">ที่</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center max-lg:px-1.5 max-lg:py-1.5">รายการ</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center max-lg:w-[7.5rem] max-lg:px-1.5 max-lg:py-1.5">จำนวนเงิน (บาท)</th>
                                    </tr>
                                </thead>
                                <tbody id="withdraw-detail-items-body"></tbody>
                                <tfoot>
                                    <tr class="font-semibold">
                                        <td class="border border-slate-300 px-2 py-1 text-center max-lg:px-1.5 max-lg:py-1.5" colspan="2">รวม</td>
                                        <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5">
                                            <div id="withdraw-detail-items-total" class="max-lg:tabular-nums">0.00</div>
                                            <div class="mt-0.5 text-xs font-bold leading-relaxed text-slate-700">
                                                (<span id="withdraw-detail-items-total-text">ศูนย์บาทถ้วน</span>)
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="wd-field grid grid-cols-[76px_1fr] items-start gap-2" data-detail-memo-block="paragraph3">
                        <div class="wd-label-spacer pt-1 font-semibold"></div>
                        <div id="withdraw-detail-paragraph3" class="wd-value min-h-10 px-1 py-1 whitespace-pre-wrap">-</div>
                    </div>
                    <div class="py-2 hidden" data-detail-memo-block="project-budget-summary" id="withdraw-detail-project-budget-summary">
                        <div class="overflow-x-auto">
                            <table class="wd-table min-w-[640px] w-full border-collapse border border-slate-300 text-sm">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">งบประมาณโครงการ</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">อนุมัติแล้ว</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">อนุมัติครั้งนี้</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">รวมอนุมัติ</th>
                                        <th class="border border-slate-300 px-2 py-1 text-center whitespace-normal break-words">คงเหลือ</th>
                                    </tr>
                                </thead>
                                <tbody id="withdraw-detail-project-budget-summary-body">
                                    <tr>
                                        <td class="border border-slate-300 px-2 py-2 text-center text-slate-400" colspan="5">- ยังไม่มีข้อมูลโครงการ -</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="wd-field grid grid-cols-[76px_1fr] items-start gap-2" data-detail-memo-block="paragraph4">
                        <div class="wd-label-spacer pt-1 font-semibold"></div>
                        <div id="withdraw-detail-paragraph4" class="wd-value min-h-10 px-1 py-1 whitespace-pre-wrap">-</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-1">
            <div class="mb-3 text-base font-bold tracking-tight text-slate-900 sm:text-lg">เอกสารประกอบการเบิกจ่าย</div>
            <div id="withdraw-detail-documents" data-withdraw-document-preview class="space-y-3 text-sm text-slate-700"></div>
        </div>

        <div class="p-1">
            <div id="withdraw-detail-approval-route-section" class="mt-3 pt-3">
                <div class="mb-3 text-base font-bold tracking-tight text-slate-900 sm:text-lg">เส้นทางการอนุมัติ</div>
                <div id="withdraw-detail-approval-route" class="space-y-0 text-sm text-slate-700"></div>
            </div>
        </div>
    </div>

    <div class="wd-close-bar flex justify-end gap-2 border-t border-slate-100 pt-4">
        <button type="button" @click="$dispatch('close-modal', 'withdraw-general-detail-modal')"
            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50">
            <i class="fas fa-times mr-1"></i>
            ปิดหน้านี้
        </button>
    </div>
</x-modal>

@once
    @php
        $withdrawDetailMemoBlockOrder = is_array($memoTemplate['block_order'] ?? null) && ($memoTemplate['block_order'] ?? []) !== []
            ? $memoTemplate['block_order']
            : ['paragraph1', 'paragraph2', 'items', 'paragraph3', 'paragraph4'];
        // project-budget-summary is inserted after paragraph 3 only for project details (JS).
        $withdrawDetailMemoBlockOrder = array_values(array_filter(
            $withdrawDetailMemoBlockOrder,
            static fn ($key) => $key !== 'project-budget-summary'
        ));
    @endphp
    @push('scripts')
        <script>
            document.addEventListener('alpine:initialized', () => {
                if (window.WithdrawGeneralDetailModal?.openById) return;

                const generalDetailApiBase = @json(url('/withdraw/api/general'));
                const nextApproverApprovalLinkBase = @json(url(\App\Support\WithdrawApprovalLink::approvalPath()));
                const nextApproverDocumentReviewLinkBase = @json(url(\App\Support\WithdrawApprovalLink::documentReviewPath()));
                const staffTokenRefreshUrl = @json((string) config('services.staff_rx_api.token_refresh_url', ''));
                const staffRxListStaffUrl = @json((string) config('services.staff_rx_api.list_staff_url', ''));
                const departmentApiUrl = @json((string) config('services.department_api.url', ''));
                const departmentApiToken = @json((string) config('services.department_api.token', ''));

                const detailCodeEl = document.getElementById('withdraw-detail-code');
                const detailDateEl = document.getElementById('withdraw-detail-date');
                const detailStatusEl = document.getElementById('withdraw-detail-status');
                const detailDepartmentEl = document.getElementById('withdraw-detail-department');
                const detailSubjectEl = document.getElementById('withdraw-detail-subject');
                const detailToEl = document.getElementById('withdraw-detail-to');
                const detailParagraph1El = document.getElementById('withdraw-detail-paragraph1');
                const detailParagraph2El = document.getElementById('withdraw-detail-paragraph2');
                const detailParagraph3El = document.getElementById('withdraw-detail-paragraph3');
                const detailParagraph4El = document.getElementById('withdraw-detail-paragraph4');
                const detailProjectBudgetSummaryEl = document.getElementById('withdraw-detail-project-budget-summary');
                const detailProjectBudgetSummaryBodyEl = document.getElementById('withdraw-detail-project-budget-summary-body');
                const detailItemsBodyEl = document.getElementById('withdraw-detail-items-body');
                const detailItemsTotalEl = document.getElementById('withdraw-detail-items-total');
                const detailItemsTotalTextEl = document.getElementById('withdraw-detail-items-total-text');
                const detailDocumentsEl = document.getElementById('withdraw-detail-documents');
                const detailApprovalRouteSectionEl = document.getElementById('withdraw-detail-approval-route-section');
                const detailApprovalRouteEl = document.getElementById('withdraw-detail-approval-route');

                const state = {
                    staffToken: '',
                    departmentNameMap: {},
                    departmentMetaMap: {},
                    staffLabelMap: {},
                    referencesLoaded: false,
                    lastLoadedRow: null,
                };

                const escapeHtml = (unsafe) => String(unsafe ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
                }[char]));
                const detailMemoBodyEl = document.getElementById('withdraw-detail-memo-body');
                const PROJECT_DETAIL_LABELS = ['รายละเอียดค่าใช้จ่ายทั้งสิ้น', 'รายละเอียดการดำเนินการ', 'กิจกรรม'];
                const applyDetailMemoBlockOrder = () => {
                    if (!detailMemoBodyEl) return;
                    const order = @json($withdrawDetailMemoBlockOrder);
                    const blocks = Array.from(detailMemoBodyEl.querySelectorAll('[data-detail-memo-block]'));
                    const blockMap = new Map(blocks.map((el) => [String(el.getAttribute('data-detail-memo-block') || '').trim(), el]));
                    order.forEach((key) => {
                        const block = blockMap.get(key);
                        if (!block) return;
                        block.classList.remove('hidden');
                        detailMemoBodyEl.appendChild(block);
                    });
                    blocks.forEach((block) => {
                        const key = String(block.getAttribute('data-detail-memo-block') || '').trim();
                        if (!order.includes(key)) {
                            block.classList.add('hidden');
                        }
                    });
                };

                const formatDetailMoney = (value) => {
                    const amount = Number(value || 0);
                    const safe = Number.isFinite(amount) ? amount : 0;
                    return safe.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                };

                const renderDetailProjectBudgetSummary = (row = {}) => {
                    const summaryBlock = detailProjectBudgetSummaryEl
                        || detailMemoBodyEl?.querySelector('[data-detail-memo-block="project-budget-summary"]');
                    const body = detailProjectBudgetSummaryBodyEl
                        || document.getElementById('withdraw-detail-project-budget-summary-body');
                    if (!summaryBlock || !body) return;

                    const isProjectDetail = String(row.withdraw_type || '').trim() === 'project';
                    if (!isProjectDetail) {
                        summaryBlock.classList.add('hidden');
                        body.innerHTML = '<tr><td class="border border-slate-300 px-2 py-2 text-center text-slate-400" colspan="5">- ยังไม่มีข้อมูลโครงการ -</td></tr>';
                        return;
                    }

                    // Keep this block right after paragraph 3, even if memo block_order changes.
                    const paragraph3Block = detailParagraph3El?.closest('[data-detail-memo-block="paragraph3"]');
                    if (paragraph3Block?.parentNode) {
                        paragraph3Block.parentNode.insertBefore(summaryBlock, paragraph3Block.nextSibling);
                    }
                    summaryBlock.classList.remove('hidden');

                    const budgetAmount = Number(row.project_amount || 0) || 0;
                    const approvedAlready = Number(row.approved_already || 0) || 0;
                    const approvedThisTime = Number(row.total_amount || 0) || 0;
                    const totalApproved = approvedAlready + approvedThisTime;
                    const remaining = budgetAmount - totalApproved;

                    body.innerHTML = `
                        <tr>
                            <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5 max-lg:tabular-nums">${formatDetailMoney(budgetAmount)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5 max-lg:tabular-nums">${formatDetailMoney(approvedAlready)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5 max-lg:tabular-nums">${formatDetailMoney(approvedThisTime)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5 max-lg:tabular-nums">${formatDetailMoney(totalApproved)}</td>
                            <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5 max-lg:tabular-nums ${remaining < 0 ? 'text-rose-600 font-semibold' : ''}">${formatDetailMoney(remaining)}</td>
                        </tr>
                    `;
                };
                const formatProjectDetailParagraph = (text) => {
                    const paragraphText = String(text || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n').trim();
                    if (paragraphText === '') return '-';

                    const parts = [];
                    const narrativeBuffer = [];
                    const flushNarrative = () => {
                        if (narrativeBuffer.length === 0) return;
                        const joined = narrativeBuffer.join(' ').trim();
                        narrativeBuffer.length = 0;
                        if (joined === '') return;
                        parts.push(`<p class="mb-2 text-justify leading-relaxed last:mb-0" style="text-indent:20mm">${escapeHtml(joined)}</p>`);
                    };

                    paragraphText.split('\n').forEach((rawLine) => {
                        const trimmed = String(rawLine || '').trim();
                        if (trimmed === '') return;

                        let matchedLabel = null;
                        for (const label of PROJECT_DETAIL_LABELS) {
                            if (trimmed.startsWith(label)) {
                                matchedLabel = label;
                                break;
                            }
                        }
                        if (matchedLabel !== null) {
                            flushNarrative();
                            const value = trimmed.slice(matchedLabel.length).trim();
                            parts.push(
                                `<div class="mb-1 grid grid-cols-[minmax(11rem,auto)_1fr] items-start gap-x-2 leading-relaxed" style="padding-left:20mm">`
                                + `<strong class="whitespace-nowrap">${escapeHtml(matchedLabel)}</strong>`
                                + `<span>${escapeHtml(value)}</span>`
                                + `</div>`
                            );
                            return;
                        }

                        const expenseMatch = trimmed.match(/^-\s*(.+?)(?:\s{2,}|\t+)(.+?\s*บาท)\s*$/u);
                        if (expenseMatch) {
                            flushNarrative();
                            parts.push(
                                `<div class="mb-0.5 grid grid-cols-[1fr_auto] items-start gap-x-3 pl-14 leading-relaxed">`
                                + `<span>- ${escapeHtml(String(expenseMatch[1] || '').trim())}</span>`
                                + `<span class="whitespace-nowrap tabular-nums">${escapeHtml(String(expenseMatch[2] || '').trim())}</span>`
                                + `</div>`
                            );
                            return;
                        }

                        if (trimmed.startsWith('- ')) {
                            flushNarrative();
                            parts.push(`<div class="mb-0.5 pl-14 leading-relaxed">- ${escapeHtml(trimmed.slice(2).trim())}</div>`);
                            return;
                        }

                        narrativeBuffer.push(trimmed);
                    });

                    flushNarrative();
                    return parts.length > 0 ? parts.join('') : '-';
                };
                const setDetailParagraphContent = (el, text, isProject) => {
                    if (!el) return;
                    const field = el.closest('.wd-field');
                    const spacer = field?.querySelector('.wd-label-spacer');
                    field?.classList.remove('grid', 'grid-cols-[76px_1fr]', 'gap-2');
                    spacer?.classList.add('hidden');
                    el.classList.remove('whitespace-pre-wrap');
                    const value = String(text || '').trim();
                    if (isProject) {
                        el.innerHTML = formatProjectDetailParagraph(value);
                        return;
                    }
                    if (value === '') {
                        el.textContent = '-';
                        return;
                    }
                    const collapsed = value
                        .replace(/\r\n/g, '\n')
                        .replace(/\r/g, '\n')
                        .replace(/[ \t\u00A0\u200B-\u200D\uFEFF]+/g, ' ')
                        .replace(/(?<!\n)\n(?!\n)/g, ' ');
                    const parts = collapsed.split(/\n{2,}/).map((part) => part.trim()).filter(Boolean);
                    const blocks = parts.length > 0 ? parts : [value];
                    el.innerHTML = blocks.map((part) => (
                        `<p class="mb-2 text-justify leading-relaxed last:mb-0" style="text-indent:20mm">${escapeHtml(part)}</p>`
                    )).join('');
                };
                const copyTextToClipboard = async (text) => {
                    const value = String(text || '').trim();
                    if (value === '') throw new Error('ไม่พบลิงก์สำหรับคัดลอก');
                    if (navigator.clipboard?.writeText) {
                        await navigator.clipboard.writeText(value);
                        return;
                    }
                    const input = document.createElement('textarea');
                    input.value = value;
                    input.setAttribute('readonly', '');
                    input.style.position = 'fixed';
                    input.style.left = '-9999px';
                    document.body.appendChild(input);
                    input.select();
                    document.execCommand('copy');
                    document.body.removeChild(input);
                };
                const buildNextApproverLink = (recordId, stepCode) => {
                    const id = Number(recordId || 0);
                    if (id <= 0) return '';
                    const base = String(stepCode || '').trim() === 'finance_officer'
                        ? nextApproverDocumentReviewLinkBase
                        : nextApproverApprovalLinkBase;
                    try {
                        const url = new URL(base, window.location.origin);
                        url.searchParams.set('id', String(id));
                        return url.toString();
                    } catch (error) {
                        const joiner = String(base).includes('?') ? '&' : '?';
                        return `${base}${joiner}id=${id}`;
                    }
                };
                const parseJsonSafely = async (response) => {
                    try { return await response.json(); } catch (error) { return null; }
                };
                const resolveDisplayDocumentNumber = (...candidates) => {
                    for (const candidate of candidates) {
                        const value = String(candidate || '').trim();
                        if (value !== '' && value !== '-') return value;
                    }
                    return '-';
                };
                const formatThaiDate = (value) => {
                    const raw = String(value || '').trim();
                    if (raw === '') return '-';
                    const ymdMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                    let dateValue = null;
                    if (ymdMatch) {
                        dateValue = new Date(Number(ymdMatch[1]), Number(ymdMatch[2]) - 1, Number(ymdMatch[3]));
                    } else {
                        const parsed = new Date(raw);
                        if (!Number.isNaN(parsed.getTime())) dateValue = parsed;
                    }
                    if (!dateValue || Number.isNaN(dateValue.getTime())) return raw;
                    return dateValue.toLocaleDateString('th-TH', { day: '2-digit', month: 'long', year: 'numeric' });
                };
                const formatThaiDateTime = (value) => {
                    const raw = String(value || '').trim();
                    if (raw === '') return '-';
                    const parsed = new Date(raw);
                    if (Number.isNaN(parsed.getTime())) return raw;
                    const datePart = parsed.toLocaleDateString('th-TH', { day: '2-digit', month: 'long', year: 'numeric' });
                    const timePart = parsed.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                    return `${datePart} ${timePart} น.`;
                };
                const toThaiBahtText = (amount) => {
                    const normalizedAmount = Number(amount || 0);
                    if (Number.isNaN(normalizedAmount) || normalizedAmount < 0) return '-';
                    const digitText = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
                    const unitText = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
                    const convertInteger = (numText) => {
                        if (numText === '' || /^0+$/.test(numText)) return 'ศูนย์';
                        let output = '';
                        const length = numText.length;
                        for (let index = 0; index < length; index += 1) {
                            const digit = Number(numText[index]);
                            const position = length - index - 1;
                            const unitIndex = position % 6;
                            if (digit === 0) { if (unitIndex === 0 && position >= 6) output += unitText[6]; continue; }
                            if (unitIndex === 0 && position >= 6) output += unitText[6];
                            if (unitIndex === 1) {
                                if (digit === 1) output += unitText[1];
                                else if (digit === 2) output += `ยี่${unitText[1]}`;
                                else output += `${digitText[digit]}${unitText[1]}`;
                                continue;
                            }
                            if (unitIndex === 0 && digit === 1 && length > 1) { output += 'เอ็ด'; continue; }
                            output += `${digitText[digit]}${unitText[unitIndex]}`;
                        }
                        return output;
                    };
                    const fixedAmount = normalizedAmount.toFixed(2);
                    const [bahtPartRaw, satangPartRaw] = fixedAmount.split('.');
                    const bahtText = convertInteger(bahtPartRaw);
                    if (Number(satangPartRaw || 0) === 0) return `${bahtText}บาทถ้วน`;
                    return `${bahtText}บาท${convertInteger(String(satangPartRaw || '0'))}สตางค์`;
                };

                const getStaffToken = async () => {
                    if (state.staffToken !== '') return state.staffToken;
                    if (!staffTokenRefreshUrl) return '';
                    const response = await fetch(staffTokenRefreshUrl, { headers: { Accept: 'application/json' } });
                    const payload = await parseJsonSafely(response);
                    const token = String(payload?.raw_token || payload?.token || '').trim().replace(/^Bearer\s+/i, '');
                    state.staffToken = token;
                    return token;
                };

                const loadReferenceData = async () => {
                    if (state.referencesLoaded) return;
                    state.referencesLoaded = true;
                    try {
                        if (departmentApiUrl) {
                            const headers = { Accept: 'application/json' };
                            if (departmentApiToken) headers.Authorization = String(departmentApiToken).startsWith('Bearer ') ? String(departmentApiToken) : `Bearer ${String(departmentApiToken)}`;
                            const response = await fetch(departmentApiUrl, { headers });
                            const payload = await parseJsonSafely(response);
                            const rows = Array.isArray(payload?.data) ? payload.data : [];
                            const nextNameMap = {};
                            const nextMetaMap = {};
                            rows.forEach((item) => {
                                const depCode = String(item?.dep_code || item?.department_code || item?.code || '').trim();
                                const depName = String(item?.dep_name || item?.department_name || item?.name || '').trim();
                                const jobCode = String(item?.job_code || '').trim();
                                const jobName = String(item?.job_name || '').trim();
                                const unitCode = String(item?.unit_code || '').trim();
                                const agencyName = String(item?.agency_name || item?.unit_name || '').trim();
                                if (depCode === '' || depName === '') return;

                                // dep level
                                nextNameMap[depCode] = depName;
                                nextMetaMap[depCode] = { departmentName: depName, jobName: '', unitName: '', label: depName };

                                // job level
                                if (jobCode !== '' && jobName !== '') {
                                    const label = `${depName} > ${jobName}`;
                                    nextNameMap[jobCode] = label;
                                    nextMetaMap[jobCode] = { departmentName: depName, jobName, unitName: '', label };
                                }

                                // agency/unit level
                                if (agencyName !== '') {
                                    const agencyValue = unitCode !== '' ? unitCode : (jobCode !== '' ? jobCode : depCode);
                                    const label = [depName, jobName, agencyName].filter(Boolean).join(' > ');
                                    nextNameMap[agencyValue] = label;
                                    nextMetaMap[agencyValue] = { departmentName: depName, jobName, unitName: agencyName, label };
                                }
                            });
                            state.departmentNameMap = nextNameMap;
                            state.departmentMetaMap = nextMetaMap;
                        }
                    } catch (error) {}
                    try {
                        if (staffRxListStaffUrl) {
                            const token = await getStaffToken();
                            const headers = { Accept: 'application/json' };
                            if (token !== '') headers.Authorization = `Bearer ${token}`;
                            const response = await fetch(staffRxListStaffUrl, { headers });
                            const payload = await parseJsonSafely(response);
                            const rows = Array.isArray(payload?.data) ? payload.data : [];
                            rows.forEach((row) => {
                                const id = String(row?.id || '').trim();
                                if (id === '') return;
                                const namePrefix = String(row?.name_prefix_full || row?.name_prefix || '').trim();
                                const name = `${namePrefix}${String(row?.fname || '').trim()} ${String(row?.lname || '').trim()}`.trim() || String(row?.name || '').trim();
                                const position = String(row?.name_position || row?.position || '').trim();
                                const dept = [String(row?.department_name || row?.dep_name || '').trim(), String(row?.job_name || '').trim(), String(row?.unit_name || '').trim()].filter(Boolean).join(' > ');
                                const label = [name, [position, dept].filter(Boolean).join(' / ')].filter(Boolean).join(' - ');
                                if (label !== '') state.staffLabelMap[id] = label;
                            });
                        }
                    } catch (error) {}
                };

                const openById = async (id, options = {}) => {
                    const recordId = Number(id || 0);
                    if (recordId <= 0) return;
                    const fallbackColumns = Array.isArray(options?.fallbackColumns) ? options.fallbackColumns : [];
                    const shouldOpenModal = options?.openModal !== false;
                    try {
                        await loadReferenceData();
                        const response = await fetch(`${generalDetailApiBase}/${recordId}`, { headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        if (result?.status !== 'success') {
                            window.toast?.error?.(result?.message || 'ไม่พบข้อมูลที่ต้องการ', { timer: 1400 });
                            return;
                        }
                        const row = result.data || {};
                        state.lastLoadedRow = row;
                        const rawStatus = String(row.raw_status || '').trim().toLowerCase();
                        const statusText = String(fallbackColumns[6]?.textContent || row.status || '-').trim() || '-';
                        const isProcessed = !['', 'saved', 'draft'].includes(rawStatus) && statusText !== 'ร่าง';
                        if (detailCodeEl) {
                            const documentNumberFromWithdrawDocument = resolveDisplayDocumentNumber(
                                row.document_number_display,
                                row.document_number,
                                fallbackColumns[1]?.textContent,
                                row.code
                            );
                            detailCodeEl.textContent = documentNumberFromWithdrawDocument;
                        }
                        if (detailDateEl) detailDateEl.textContent = formatThaiDate(row.document_date);
                        if (detailStatusEl) detailStatusEl.textContent = statusText;
                        if (detailDepartmentEl) {
                            const departmentCode = String(row.department_code || '').trim();
                            const mappedMeta = state.departmentMetaMap[departmentCode] || {};
                            const mappedLabel = String(state.departmentNameMap[departmentCode] || '').trim();
                            const mappedParts = mappedLabel.split('>').map((item) => item.trim()).filter(Boolean);
                            const departmentName = String(row.department_name || mappedMeta.departmentName || mappedParts[0] || '').trim();
                            const unitName = String(row.unit_name || mappedMeta.unitName || mappedParts[2] || mappedParts[0] || '').trim();
                            const jobName = String(row.job_name || mappedMeta.jobName || mappedParts[1] || '').trim();
                            const jobOrDepartmentName = jobName !== '' ? jobName : departmentName;
                            detailDepartmentEl.textContent = unitName !== '' && jobOrDepartmentName !== '' ? `${unitName}  ${jobOrDepartmentName}` : (unitName || jobOrDepartmentName || mappedLabel || departmentCode || '-');
                        }

                        const resolveStaffLabel = (staffId) => String(state.staffLabelMap[String(staffId || '').trim()] || '').trim();
                        const isProjectDetail = String(row.withdraw_type || '').trim() === 'project';
                        if (detailSubjectEl) detailSubjectEl.textContent = String(row.subject || '-');
                        if (detailToEl) detailToEl.textContent = String(row.to || '-');
                        applyDetailMemoBlockOrder();
                        setDetailParagraphContent(detailParagraph1El, row.paragraph1, isProjectDetail);
                        setDetailParagraphContent(detailParagraph2El, row.paragraph2, isProjectDetail);
                        setDetailParagraphContent(detailParagraph3El, row.paragraph3, isProjectDetail);
                        setDetailParagraphContent(detailParagraph4El, row.paragraph4, isProjectDetail);
                        const isDetailParagraphEnabled = (key) => {
                            const flags = row?.paragraph_enabled;
                            if (!flags || typeof flags !== 'object' || flags[key] === undefined) return true;
                            return flags[key] !== false && flags[key] !== 0 && flags[key] !== '0';
                        };
                        [
                            ['paragraph1', detailParagraph1El],
                            ['paragraph2', detailParagraph2El],
                            ['paragraph3', detailParagraph3El],
                            ['paragraph4', detailParagraph4El],
                        ].forEach(([key, el]) => {
                            const block = el?.closest('[data-detail-memo-block]');
                            if (!block || isDetailParagraphEnabled(key)) return;
                            block.classList.add('hidden');
                        });
                        const paragraph4Block = detailParagraph4El?.closest('[data-detail-memo-block="paragraph4"]');
                        if (paragraph4Block && isDetailParagraphEnabled('paragraph4')) {
                            paragraph4Block.classList.toggle('hidden', String(row.paragraph4 || '').trim() === '');
                        }
                        renderDetailProjectBudgetSummary(row);

                        if (detailItemsBodyEl && detailItemsTotalEl) {
                            const items = Array.isArray(row.items) ? row.items : [];
                            let totalAmount = 0;
                            if (items.length > 0) {
                                const groupedItems = [];
                                const groupedMap = new Map();
                                items.forEach((item) => {
                                    const category = String(item.category || '-').trim() || '-';
                                    const categoryCode = String(item.category_code || '').trim();
                                    const name = String(item.name || '-').trim() || '-';
                                    const safeAmount = Number.isNaN(Number(item.amount || 0)) ? 0 : Number(item.amount || 0);
                                    totalAmount += safeAmount;
                                    const groupKey = `${categoryCode}::${category}`;
                                    if (!groupedMap.has(groupKey)) {
                                        const group = { category, categoryCode, items: [], total: 0 };
                                        groupedMap.set(groupKey, group);
                                        groupedItems.push(group);
                                    }
                                    const group = groupedMap.get(groupKey);
                                    group.items.push({
                                        id: Number(item.id || 0),
                                        name,
                                        amount: safeAmount,
                                        expense_chart_id: Number(item.expense_chart_id || 0),
                                        expense_chart_name: String(item.expense_chart_name || item.display_name || '').trim(),
                                        budget_manage_id: Number(item.budget_manage_id || 0),
                                    });
                                    group.total += safeAmount;
                                });

                                detailItemsBodyEl.innerHTML = groupedItems.map((group, index) => {
                                    const categoryRow = `<tr class="bg-slate-50/60 font-semibold" data-main-category-code="${escapeHtml(group.categoryCode || '')}">
                                        <td class="border border-slate-300 px-2 py-1 text-center max-lg:px-1.5 max-lg:py-1.5">${index + 1}</td>
                                        <td class="border border-slate-300 px-2 py-1 max-lg:break-words max-lg:px-1.5 max-lg:py-1.5">${escapeHtml(group.category)}</td>
                                        <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5 max-lg:tabular-nums">${group.total.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                    </tr>`;
                                    const subRows = group.items.map((subItem) => `<tr data-withdraw-item-id="${Number(subItem.id || 0)}" data-item-name="${escapeHtml(subItem.name)}" data-expense-chart-id="${Number(subItem.expense_chart_id || 0)}" data-expense-chart-name="${escapeHtml(subItem.expense_chart_name || '')}" data-budget-manage-id="${Number(subItem.budget_manage_id || 0)}">
                                        <td class="border border-slate-300 px-2 py-1 text-center max-lg:px-1.5 max-lg:py-1.5"></td>
                                        <td class="border border-slate-300 px-2 py-1 max-lg:break-words max-lg:px-1.5 max-lg:py-1.5">- ${escapeHtml(subItem.name)}</td>
                                        <td class="border border-slate-300 px-2 py-1 text-right max-lg:px-1.5 max-lg:py-1.5 max-lg:tabular-nums">${subItem.amount.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                    </tr>`).join('');
                                    return `${categoryRow}${subRows}`;
                                }).join('');
                            } else detailItemsBodyEl.innerHTML = '<tr><td class="border border-slate-300 px-2 py-2 text-center text-slate-400" colspan="3">- ไม่มีรายการ -</td></tr>';
                            detailItemsTotalEl.textContent = totalAmount.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            if (detailItemsTotalTextEl) detailItemsTotalTextEl.textContent = toThaiBahtText(totalAmount);
                        }

                        if (detailDocumentsEl) {
                            window.WithdrawDocumentPreview?.fill(detailDocumentsEl, row);
                        }

                        if (detailApprovalRouteEl) {
                            const approvalSteps = Array.isArray(row.approval_steps) ? row.approval_steps : [];
                            const statusLabelMap = {
                                pending: 'รอดำเนินการ',
                                approved: 'อนุมัติแล้ว',
                                rejected: 'ส่งกลับให้แก้ไข',
                                returned: 'แก้ไขแล้ว',
                                skipped: 'ข้ามขั้นตอน',
                                submitted: 'ส่งรายการแล้ว',
                            };
                            const statusClassMap = {
                                pending: 'bg-amber-50 text-amber-700 ring-amber-200',
                                approved: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                rejected: 'bg-rose-50 text-rose-700 ring-rose-200',
                                returned: 'bg-sky-50 text-sky-700 ring-sky-200',
                                skipped: 'bg-slate-50 text-slate-600 ring-slate-200',
                                submitted: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                            };
                            const statusAccentMap = {
                                pending: { dot: 'bg-amber-500 text-white', line: 'bg-amber-200', card: 'border-amber-100 bg-amber-50/80', icon: 'fa-hourglass-half' },
                                approved: { dot: 'bg-emerald-500 text-white', line: 'bg-emerald-200', card: 'border-slate-200 bg-white', icon: 'fa-check' },
                                rejected: { dot: 'bg-rose-500 text-white', line: 'bg-rose-200', card: 'border-rose-100 bg-rose-50/80', icon: 'fa-rotate-left' },
                                returned: { dot: 'bg-sky-500 text-white', line: 'bg-sky-200', card: 'border-sky-100 bg-sky-50/80', icon: 'fa-reply' },
                                skipped: { dot: 'bg-slate-400 text-white', line: 'bg-slate-200', card: 'border-slate-200 bg-slate-50', icon: 'fa-minus' },
                                submitted: { dot: 'bg-emerald-500 text-white', line: 'bg-emerald-200', card: 'border-slate-200 bg-white', icon: 'fa-check' },
                            };
                            const routeSteps = approvalSteps.map((step, index) => ({ ...step, __origin_index: index }));
                            const approvalStepHistory = Array.isArray(row.approval_step_history) ? row.approval_step_history : [];
                            const actionLogs = Array.isArray(row.action_logs) ? row.action_logs : [];
                            const applicantStaffId = String(row.applicant_staff_id || '').trim();
                            const applicantSubmitLog = actionLogs.find((log) => ['submit_for_reviewer', 'submit_revision_update'].includes(String(log?.action_code || '').trim()))
                                || actionLogs.find((log) => String(log?.action_code || '').trim() === 'save_draft')
                                || null;
                            const applicantActionAt = String(
                                applicantSubmitLog?.action_at
                                || row.document_date
                                || row.withdraw_date_create
                                || row.date_create
                                || ''
                            ).trim();
                            const isApplicantDraft = ['', 'saved', 'draft'].includes(String(row.raw_status || '').trim().toLowerCase());
                            if (applicantStaffId !== '' || String(row.applicant_name || '').trim() !== '') {
                                routeSteps.unshift({
                                    step_code: 'applicant',
                                    step_name: 'ผู้ขอเบิก',
                                    staff_id: applicantStaffId,
                                    staff_name: String(row.applicant_name || '').trim(),
                                    status: isApplicantDraft ? 'pending' : 'submitted',
                                    comment: '',
                                    approved_at: applicantActionAt,
                                    custom_status: '',
                                    step_order: 0,
                                    __origin_index: -100,
                                });
                            }
                            const revisionComment = String(row.revision_comment || '').trim();
                            const statusTextForRoute = String(row.status || '').trim();
                            const isRevisionLabel = statusTextForRoute.includes('(แก้ไข)');
                            const financeOfficerStep = approvalSteps.find((step) => String(step?.step_code || '').trim() === 'finance_officer') || null;
                            const financeManagerStep = approvalSteps.find((step) => String(step?.step_code || '').trim() === 'finance_manager') || null;
                            const hasLegacyManagerRevisionEvidence = approvalSteps.some((step) =>
                                String(step?.step_code || '').trim() === 'finance_manager'
                                && String(step?.status || '').trim().toLowerCase() === 'pending'
                                && String(step?.approved_at || '').trim() !== ''
                            );
                            const fallbackStepCode = isRevisionLabel ? 'finance_manager' : 'finance_officer';
                            const fallbackStep = fallbackStepCode === 'finance_manager'
                                ? (financeManagerStep || financeOfficerStep)
                                : (financeOfficerStep || financeManagerStep);
                            const historyRevisionEvents = approvalStepHistory
                                .filter((item) => String(item?.event || '').trim() === 'revision_requested')
                                .map((item, eventIndex) => ({
                                    step_code: `revision_history_${String(item?.step_code || 'step').trim()}_${eventIndex}`,
                                    step_name: String(item?.label || 'ส่งกลับให้แก้ไข').trim(),
                                    step_anchor_code: String(item?.step_code || '').trim(),
                                    staff_id: String(item?.staff_id || item?.recorded_by || '').trim(),
                                    staff_name: '',
                                    status: String(item?.status || 'rejected').trim().toLowerCase() || 'rejected',
                                    comment: String(item?.comment || '').trim(),
                                    approved_at: String(item?.recorded_at || '').trim(),
                                    custom_status: '',
                                    step_order: Number(item?.step_order || 0),
                                    __origin_index: -0.5 + (eventIndex * 0.001),
                                }));
                            const logRevisionEvents = actionLogs
                                .filter((log) => ['request_revision_to_finance', 'request_revision'].includes(String(log?.action_code || '').trim()))
                                .map((log, eventIndex) => ({
                                    step_code: `revision_log_${eventIndex}`,
                                    step_name: 'ส่งกลับให้แก้ไข',
                                    step_anchor_code: String(log?.action_code || '').trim() === 'request_revision_to_finance' ? 'finance_manager' : 'finance_officer',
                                    staff_id: String(log?.actor_staff_id || '').trim(),
                                    staff_name: String(log?.actor_name || '').trim(),
                                    status: 'rejected',
                                    comment: String(log?.comment || '').trim(),
                                    approved_at: String(log?.action_at || '').trim(),
                                    custom_status: '',
                                    step_order: String(log?.action_code || '').trim() === 'request_revision_to_finance' ? 3 : 2,
                                    __origin_index: -0.6 + (eventIndex * 0.001),
                                }));
                            const logReturnedEvents = actionLogs
                                .filter((log) => String(log?.action_code || '').trim() === 'submit_revision_update')
                                .map((log, eventIndex) => ({
                                    step_code: `revision_return_log_${eventIndex}`,
                                    step_name: 'ส่งกลับมาให้อนุมัติ',
                                    step_anchor_code: 'finance_officer',
                                    staff_id: String(log?.actor_staff_id || '').trim(),
                                    staff_name: String(log?.actor_name || '').trim(),
                                    status: 'returned',
                                    comment: String(log?.comment || '').trim(),
                                    approved_at: String(log?.action_at || '').trim(),
                                    custom_status: '',
                                    step_order: 2,
                                    __origin_index: -0.55 + (eventIndex * 0.001),
                                }));
                            const hasRevisionEvent = historyRevisionEvents.length > 0
                                || revisionComment !== ''
                                || isRevisionLabel
                                || hasLegacyManagerRevisionEvidence;
                            if (historyRevisionEvents.length === 0 && logRevisionEvents.length === 0 && hasRevisionEvent) {
                                historyRevisionEvents.push({
                                    step_code: 'revision_history_fallback',
                                    step_name: 'ส่งกลับให้แก้ไข',
                                    step_anchor_code: fallbackStepCode,
                                    staff_id: String(fallbackStep?.staff_id || '').trim(),
                                    staff_name: String(fallbackStep?.staff_name || '').trim(),
                                    status: 'rejected',
                                    comment: revisionComment !== '' ? revisionComment : 'มีประวัติส่งกลับให้แก้ไข',
                                    approved_at: String(fallbackStep?.approved_at || '').trim(),
                                    custom_status: '',
                                    step_order: Number(fallbackStep?.step_order || 0),
                                    __origin_index: -0.9,
                                });
                            }
                            const revisionEventKey = (event) => {
                                const staff = String(event?.staff_id || '').trim();
                                const comment = String(event?.comment || '').trim();
                                const ts = String(event?.approved_at || '').trim();
                                const minute = ts.length >= 16 ? ts.slice(0, 16) : ts;
                                return `${staff}|${minute}|${comment}`;
                            };
                            const mergedRevisionEvents = [];
                            const seenRevisionKeys = new Map();
                            [...historyRevisionEvents, ...logRevisionEvents].forEach((event) => {
                                const key = revisionEventKey(event);
                                if (key !== '||' && seenRevisionKeys.has(key)) {
                                    const existing = seenRevisionKeys.get(key);
                                    if (!String(existing?.staff_name || '').trim() && String(event?.staff_name || '').trim()) {
                                        existing.staff_name = event.staff_name;
                                    }
                                    return;
                                }
                                mergedRevisionEvents.push(event);
                                if (key !== '||') seenRevisionKeys.set(key, event);
                            });
                            const mergedReturnEvents = [...logReturnedEvents];
                            mergedRevisionEvents.forEach((event) => {
                                const anchorCode = String(event?.step_anchor_code || '').trim();
                                const anchorIndex = routeSteps.findIndex((step) => String(step?.step_code || '').trim() === anchorCode);
                                const insertAt = anchorIndex >= 0 ? anchorIndex : routeSteps.length;
                                routeSteps.splice(insertAt, 0, event);
                            });
                            mergedReturnEvents.forEach((event) => {
                                const anchorCode = String(event?.step_anchor_code || '').trim();
                                const anchorIndex = routeSteps.findIndex((step) => String(step?.step_code || '').trim() === anchorCode);
                                const insertAt = anchorIndex >= 0 ? anchorIndex + 1 : routeSteps.length;
                                routeSteps.splice(insertAt, 0, event);
                            });
                            const routeEventTime = (step) => {
                                const status = String(step?.status || '').trim().toLowerCase();
                                if (status === 'pending' || status === 'skipped') return 0;
                                const at = String(step?.approved_at || '').trim();
                                if (at === '' || at.startsWith('0000-00-00')) return 0;
                                const parsed = Date.parse(at.replace(' ', 'T'));
                                return Number.isFinite(parsed) ? parsed : 0;
                            };
                            routeSteps.sort((a, b) => {
                                const timeA = routeEventTime(a);
                                const timeB = routeEventTime(b);
                                if (timeA > 0 && timeB > 0 && timeA !== timeB) return timeA - timeB;
                                if (timeA > 0 && timeB <= 0) return -1;
                                if (timeA <= 0 && timeB > 0) return 1;
                                const aOrder = Number(a?.step_order || 0);
                                const bOrder = Number(b?.step_order || 0);
                                if (aOrder !== bOrder) return aOrder - bOrder;
                                return Number(a?.__origin_index || 0) - Number(b?.__origin_index || 0);
                            });
                            const applicantRouteIndex = routeSteps.findIndex((step) => String(step?.step_code || '').trim() === 'applicant');
                            if (applicantRouteIndex > 0) {
                                const [applicantRouteStep] = routeSteps.splice(applicantRouteIndex, 1);
                                routeSteps.unshift(applicantRouteStep);
                            }
                            if (detailApprovalRouteSectionEl) {
                                detailApprovalRouteSectionEl.classList.toggle('hidden', routeSteps.length === 0);
                            }
                            const recordId = Number(row.id || 0);
                            const nextPendingStepCode = String((routeSteps.find((step) => {
                                const code = String(step?.step_code || '').trim();
                                if (code === '' || code === 'applicant' || code.startsWith('revision_')) return false;
                                const status = String(step?.status || '').trim().toLowerCase();
                                const staffIdValue = String(step?.staff_id || '').trim();
                                return status === 'pending' && staffIdValue !== '';
                            }) || {}).step_code || '').trim();
                            const nextApproverLink = buildNextApproverLink(recordId, nextPendingStepCode);
                            if (routeSteps.length === 0) {
                                detailApprovalRouteEl.innerHTML = '<div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">- ยังไม่มีเส้นทางการอนุมัติ -</div>';
                            } else {
                                detailApprovalRouteEl.innerHTML = routeSteps.map((step, index) => {
                                    const stepCode = String(step.step_code || '').trim();
                                    const baseStepName = String(step.step_name || '-').trim() || '-';
                                    const staffId = String(step.staff_id || '').trim();
                                    const staffName = String(step.staff_name || '').trim();
                                    const actorLabel = staffId !== '' ? resolveStaffLabel(staffId) : '';
                                    const actorParts = actorLabel.split(' - ');
                                    const actorName = staffName || String(actorParts[0] || '').trim() || staffId || '-';
                                    const actorPosition = String(actorParts[1] || '').split('/')[0].trim();
                                    const actorDisplay = actorPosition !== '' && actorName !== '-'
                                        ? `${actorName} (${actorPosition})`
                                        : actorName;
                                    const statusKey = String(step.status || 'pending').trim().toLowerCase();
                                    const statusLabel = String(statusLabelMap[statusKey] || statusKey || '-').trim();
                                    const statusClass = String(statusClassMap[statusKey] || 'bg-slate-50 text-slate-700 ring-slate-200');
                                    const accent = statusAccentMap[statusKey] || statusAccentMap.skipped;
                                    const customStatus = String(step.custom_status || '').trim();
                                    const approvedAt = String(step.approved_at || '').trim();
                                    const shouldShowActionAt = approvedAt !== '' && !approvedAt.startsWith('0000-00-00') && statusKey !== 'pending';
                                    const actionAtLabel = stepCode === 'applicant'
                                        ? (statusKey === 'submitted' ? 'ส่งรายการเมื่อ' : 'ดำเนินการเมื่อ')
                                        : (statusKey === 'approved'
                                            ? 'อนุมัติเมื่อ'
                                            : (statusKey === 'rejected' || statusKey === 'returned' ? 'ส่งกลับเมื่อ' : 'ดำเนินการเมื่อ'));
                                    const comment = String(step.comment || '').trim();
                                    const commentLabel = stepCode.startsWith('revision_return_log_') ? 'คำชี้แจงการแก้ไข' : 'ความคิดเห็น';
                                    const stepName = stepCode === 'additional_reviewer' && customStatus !== ''
                                        ? `${baseStepName} (${customStatus})`
                                        : baseStepName;
                                    const showCopyLinkButton = recordId > 0
                                        && nextPendingStepCode !== ''
                                        && nextApproverLink !== ''
                                        && stepCode === nextPendingStepCode
                                        && statusKey === 'pending'
                                        && staffId !== ''
                                        && stepCode !== 'applicant'
                                        && !stepCode.startsWith('revision_');
                                    const nextApproverActions = showCopyLinkButton
                                        ? `<div class="flex flex-col items-end gap-1 max-lg:w-full">
                                            <button type="button" data-general-copy-approval-link data-link="${escapeHtml(nextApproverLink)}" title="คัดลอกลิงก์สำหรับส่งให้ผู้อนุมัติคนถัดไป" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-50 max-lg:w-full max-lg:justify-center max-lg:py-1.5">
                                                <i class="fas fa-link text-[10px]"></i>คัดลอกลิงก์
                                            </button>
                                        </div>`
                                        : '';
                                    const isLast = index === routeSteps.length - 1;
                                    return `<div class="relative flex gap-3 ${isLast ? '' : 'pb-3'} max-lg:gap-2">
                                        <div class="flex w-8 shrink-0 flex-col items-center max-lg:w-7">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full text-[11px] font-bold shadow-sm ring-4 ring-white max-lg:h-7 max-lg:w-7 ${accent.dot}">${index + 1}</div>
                                            ${isLast ? '' : `<div class="mt-1 w-px flex-1 ${accent.line}"></div>`}
                                        </div>
                                        <div class="min-w-0 flex-1 rounded-2xl border ${accent.card} p-3.5 shadow-sm max-lg:p-3">
                                            <div class="flex items-start justify-between gap-3 max-lg:flex-col max-lg:gap-2">
                                                <div class="min-w-0">
                                                    <div class="text-sm font-bold text-slate-900">${escapeHtml(stepName)}</div>
                                                    <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-600 max-lg:items-start">
                                                        <i class="fas fa-user text-[10px] text-slate-400 max-lg:mt-0.5"></i>
                                                        <span class="max-lg:break-words">${escapeHtml(actorDisplay)}</span>
                                                    </div>
                                                </div>
                                                <div class="flex shrink-0 flex-col items-end gap-1.5 max-lg:w-full max-lg:flex-row max-lg:flex-wrap max-lg:items-center">
                                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ${statusClass}">
                                                        <i class="fas ${accent.icon} text-[10px]"></i>${escapeHtml(statusLabel)}
                                                    </span>
                                                    ${nextApproverActions}
                                                </div>
                                            </div>
                                            ${shouldShowActionAt ? `<div class="mt-2 text-[11px] text-slate-500"><i class="far fa-clock mr-1"></i>${actionAtLabel} ${escapeHtml(formatThaiDateTime(approvedAt))}</div>` : ''}
                                            ${comment !== '' ? `<div class="mt-2 rounded-lg bg-white/80 px-2.5 py-2 text-xs text-slate-600 ring-1 ring-slate-100 whitespace-pre-wrap"><span class="font-semibold text-slate-700">${escapeHtml(commentLabel)}:</span> ${escapeHtml(comment)}</div>` : ''}
                                        </div>
                                    </div>`;
                                }).join('');
                            }
                        }

                        if (shouldOpenModal) {
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-general-detail-modal' }));
                        }
                    } catch (error) {
                        window.toast?.error?.('ไม่สามารถโหลดรายละเอียดรายการได้', { timer: 1400 });
                    }
                };

                window.WithdrawGeneralDetailModal = {
                    openById,
                    getLastLoadedRow: () => state.lastLoadedRow,
                };

                detailApprovalRouteEl?.addEventListener('click', async (event) => {
                    const copyButton = event.target.closest('[data-general-copy-approval-link]');
                    if (!copyButton || !detailApprovalRouteEl.contains(copyButton)) return;
                    event.preventDefault();
                    event.stopPropagation();
                    const link = String(copyButton.dataset.link || '').trim();
                    try {
                        await copyTextToClipboard(link);
                        window.toast?.success?.('คัดลอกลิงก์แล้ว ส่งให้ผู้อนุมัติคนถัดไปได้เลย', { timer: 1800 });
                    } catch (error) {
                        window.toast?.error?.(error?.message || 'ไม่สามารถคัดลอกลิงก์ได้', { timer: 1800 });
                    }
                });
            });
        </script>
    @endpush
@endonce
