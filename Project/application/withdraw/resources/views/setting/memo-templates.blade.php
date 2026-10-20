<x-layouts.app>
    <div class="py-2">
        <div class="mx-2 flex flex-col gap-4">
            {{-- Header --}}
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-slate-50/80 to-emerald-50/50 p-4 shadow-md shadow-slate-200/50 sm:px-4 sm:py-4">
                <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-emerald-400/15 blur-2xl sm:h-36 sm:w-36"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-emerald-200/60 to-transparent"></div>
                <div class="relative flex min-w-0 gap-4">
                    <div class="mt-0.5 flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-lg text-white shadow-lg shadow-emerald-500/30 ring-4 ring-white/80">
                        <i class="fas fa-file-lines" aria-hidden="true"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-[1.65rem]">{{ $title ?? 'เทมเพลต (บันทึกข้อความ)' }}</h4>
                        <p class="mb-0 max-w-2xl text-sm leading-snug text-slate-500">
                            กำหนดข้อความตั้งต้น ลำดับแสดงผลใน PDF และเส้นทางอนุมัติ แยกตามประเภทการเบิกจ่าย
                        </p>
                    </div>
                </div>
            </div>

            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/70 shadow-md shadow-slate-200/60">
                <div class="p-4 sm:p-5">
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
                {{-- Sidebar: type picker --}}
                <div class="xl:col-span-4">
                    <div class="xl:sticky xl:top-4">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-emerald-50/40 px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-emerald-600 shadow-sm ring-1 ring-slate-200/80">
                                        <i class="fas fa-layer-group text-sm"></i>
                                    </span>
                                    <div>
                                        <div class="text-sm font-bold text-slate-800">ประเภทการเบิกจ่าย</div>
                                        <div class="text-[11px] text-slate-500">เลือกเพื่อแก้ไขเทมเพลต</div>
                                    </div>
                                </div>
                            </div>
                            <div id="memo-template-tree" class="max-h-[min(70vh,520px)] space-y-3 overflow-y-auto p-3"></div>
                        </div>

                        <div class="mt-3 rounded-xl border border-dashed border-emerald-200/80 bg-emerald-50/50 px-3 py-2.5 text-[11px] leading-relaxed text-emerald-800">
                            <i class="fas fa-circle-info mr-1 opacity-70"></i>
                            การเปลี่ยนแปลงมีผลเมื่อสร้างรายการเบิกจ่ายใหม่ หรือพิมพ์ PDF บันทึกข้อความ
                        </div>
                    </div>
                </div>

                {{-- Main form --}}
                <div class="xl:col-span-8">
                    <form id="memo-template-form" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        {{-- Form header --}}
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-white via-slate-50/50 to-emerald-50/30 px-4 py-4 sm:px-5">
                            <div class="min-w-0 flex-1 text-left">
                                <div class="mb-0.5 flex flex-wrap items-center gap-2">
                                    <span id="memo-template-kind-badge" class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600">ทั่วไป</span>
                                    <span id="memo-template-selected-label" class="truncate text-base font-bold text-slate-900">-</span>
                                </div>
                                <p class="text-xs text-slate-500">ข้อความและลำดับด้านล่างจะถูกเติมอัตโนมัติในฟอร์มเบิกจ่าย</p>
                            </div>
                            <button type="submit" id="memo-save-button"
                                class="inline-flex shrink-0 items-center justify-center gap-2 self-center rounded-xl border border-emerald-600/20 bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300">
                                <i class="far fa-save"></i>
                                บันทึกเทมเพลต
                            </button>
                        </div>

                        <input type="hidden" id="memo-kind" value="general" />
                        <input type="hidden" id="memo-sub-type" value="default" />

                        <div class="space-y-5 p-4 sm:p-5">
                            {{-- Basic info --}}
                            <section>
                                <div class="mb-3 flex items-center gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-50 text-sky-600 ring-1 ring-sky-100">
                                        <i class="fas fa-tag text-xs"></i>
                                    </span>
                                    <h5 class="text-sm font-bold text-slate-800">ข้อมูลหัวเอกสาร</h5>
                                </div>
                                <div class="space-y-3 rounded-xl border border-slate-200/80 bg-slate-50/40 p-4">
                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-slate-700">ชื่อเทมเพลต (ใช้แสดงในระบบ)</label>
                                        <input type="text" id="memo-name" placeholder="เช่น เงินเดือน"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-slate-700">เรื่อง</label>
                                        <input type="text" id="memo-subject" placeholder="ข้อความในบรรทัด เรื่อง"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-slate-700">เรียน</label>
                                        <input type="text" id="memo-to" placeholder="ผู้รับเรื่อง"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100" />
                                    </div>
                                </div>
                            </section>

                            {{-- Paragraphs --}}
                            <section>
                                <div class="mb-3 flex items-center gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-50 text-violet-600 ring-1 ring-violet-100">
                                        <i class="fas fa-align-left text-xs"></i>
                                    </span>
                                    <h5 class="text-sm font-bold text-slate-800">เนื้อหาย่อหน้า</h5>
                                </div>
                                <div class="grid grid-cols-1 gap-3">
                                    @foreach ([1 => ['rows' => 3, 'placeholder' => 'เนื้อหาย่อหน้าแรก...'], 2 => ['rows' => 3, 'placeholder' => 'เนื้อหาย่อหน้าถัดไป...'], 3 => ['rows' => 2, 'placeholder' => 'ย่อหน้าปิดท้าย...'], 4 => ['rows' => 2, 'placeholder' => 'ย่อหน้าเพิ่มเติม...']] as $paragraphNo => $paragraphMeta)
                                        <div class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-sm" data-paragraph-card="paragraph{{ $paragraphNo }}">
                                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                                <label class="flex items-center gap-2 text-xs font-bold text-slate-700" for="memo-paragraph{{ $paragraphNo }}">
                                                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded bg-slate-100 px-1 text-[10px] font-bold text-slate-600">{{ $paragraphNo }}</span>
                                                    ย่อหน้า {{ $paragraphNo }}
                                                </label>
                                                <label class="relative inline-flex shrink-0 cursor-pointer items-center gap-2">
                                                    <span class="memo-paragraph-enabled-label text-xs font-semibold text-emerald-700">เปิด</span>
                                                    <input type="checkbox" class="memo-paragraph-enabled peer sr-only" data-paragraph-key="paragraph{{ $paragraphNo }}" checked />
                                                    <span class="relative inline-flex h-5 w-9 shrink-0 rounded-full bg-slate-300 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-slate-200 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-300"></span>
                                                </label>
                                            </div>
                                            <textarea id="memo-paragraph{{ $paragraphNo }}" rows="{{ $paragraphMeta['rows'] }}" placeholder="{{ $paragraphMeta['placeholder'] }}"
                                                class="memo-paragraph-input w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm leading-relaxed text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
                                        </div>
                                    @endforeach
                                </div>
                            </section>

                            {{-- Block order --}}
                            <section class="rounded-xl border border-amber-200/60 bg-gradient-to-br from-amber-50/80 to-white p-4">
                                <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-700 ring-1 ring-amber-200/80">
                                            <i class="fas fa-arrow-down-wide-short text-xs"></i>
                                        </span>
                                        <div>
                                            <h5 class="text-sm font-bold text-slate-800">ลำดับการแสดงใน PDF</h5>
                                            <p class="text-[11px] text-slate-500">กำหนดว่าตารางรายการเบิกจ่ายอยู่หลังย่อหน้าใด</p>
                                        </div>
                                    </div>
                                </div>
                                <div id="memo-block-order-preview" class="mb-3 flex flex-wrap items-center gap-1 rounded-lg border border-amber-100 bg-white/80 px-2 py-2 text-[10px] font-medium text-amber-900"></div>
                                <ul id="memo-block-order-list" class="space-y-2"></ul>
                            </section>

                            {{-- Approval route --}}
                            <section class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                                <div class="mb-3 flex items-center gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                                        <i class="fas fa-route text-xs"></i>
                                    </span>
                                    <div>
                                        <h5 class="text-sm font-bold text-slate-800">เส้นทางการอนุมัติ</h5>
                                        <p class="text-[11px] text-slate-500">เลือกขั้นตอนที่ใช้สำหรับประเภทนี้</p>
                                    </div>
                                </div>
                                <div id="memo-approval-steps" class="grid grid-cols-1 gap-2 sm:grid-cols-2"></div>
                                <p class="mt-3 flex items-start gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-[11px] leading-relaxed text-slate-500">
                                    <i class="fas fa-info-circle mt-0.5 shrink-0 text-slate-400"></i>
                                    <span>ลำดับเริ่มต้น: ผู้ตรวจสอบ/หัวหน้างาน → ผู้ตรวจสอบเพิ่มเติม (จากฟอร์ม) → ขั้นตอนที่เลือกด้านล่าง</span>
                                </p>
                            </section>

                            {{-- Extra actors in withdraw form --}}
                            <section class="rounded-xl border border-dashed border-slate-300 bg-white p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex min-w-0 flex-1 items-start gap-2">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                            <i class="fas fa-user-plus text-xs"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <h5 class="text-sm font-bold text-slate-800">ผู้ตรวจสอบ/อนุมัติเพิ่มเติม</h5>
                                            <p class="text-[11px] leading-relaxed text-slate-500">เปิด = แสดงส่วน «เพิ่มผู้ตรวจสอบ/อนุมัติเพิ่มเติม» ในฟอร์มเพิ่มการเบิกจ่าย · ปิด = ซ่อนส่วนนี้</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex shrink-0 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                        <span id="memo-process-actors-enabled-label" class="text-xs font-semibold text-slate-500">ปิด</span>
                                        <input type="checkbox" id="memo-process-actors-enabled" class="peer sr-only" />
                                        <span class="relative inline-flex h-5 w-9 shrink-0 rounded-full bg-slate-300 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-slate-200 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-500 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-300"></span>
                                    </label>
                                </div>
                            </section>
                        </div>
                    </form>
                </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:initialized', () => {
                const apiBase = @json($settingsApiBase ?? url('/withdraw/setting/api'));
                let catalog = @json($catalog ?? []);
                const blockLabels = @json($blockLabels ?? []);
                const approvalStepOptions = @json($approvalStepOptions ?? []);

                const kindIcons = {
                    general: 'fa-list-check',
                    salary: 'fa-coins',
                    travel: 'fa-route',
                    project: 'fa-diagram-project',
                };

                const blockIcons = {
                    paragraph1: 'fa-align-left',
                    paragraph2: 'fa-align-center',
                    paragraph3: 'fa-align-right',
                    paragraph4: 'fa-align-justify',
                    items: 'fa-table',
                };

                const treeEl = document.getElementById('memo-template-tree');
                const form = document.getElementById('memo-template-form');
                const selectedLabel = document.getElementById('memo-template-selected-label');
                const kindBadge = document.getElementById('memo-template-kind-badge');
                const blockOrderList = document.getElementById('memo-block-order-list');
                const blockOrderPreview = document.getElementById('memo-block-order-preview');
                const approvalStepsEl = document.getElementById('memo-approval-steps');
                const actorsEnabledInput = document.getElementById('memo-process-actors-enabled');
                const actorsEnabledLabel = document.getElementById('memo-process-actors-enabled-label');
                const saveButton = document.getElementById('memo-save-button');
                const kindInput = document.getElementById('memo-kind');
                const subTypeInput = document.getElementById('memo-sub-type');

                let selectedKind = 'general';
                let selectedSubType = 'default';
                let blockOrder = ['paragraph1', 'paragraph2', 'items', 'paragraph3', 'paragraph4'];
                const paragraphKeys = ['paragraph1', 'paragraph2', 'paragraph3', 'paragraph4'];
                let paragraphEnabled = {
                    paragraph1: true,
                    paragraph2: true,
                    paragraph3: true,
                    paragraph4: true,
                };

                const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

                const escapeHtml = (unsafe) => String(unsafe ?? '').replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
                })[c]);

                const findCatalogEntry = (kind, subType) => {
                    return (catalog || []).find((row) => String(row.kind) === kind && String(row.sub_type) === subType) || null;
                };

                const updateCatalogTemplate = (kind, subType, template) => {
                    catalog = (catalog || []).map((row) => {
                        if (String(row.kind) === kind && String(row.sub_type) === subType) {
                            return { ...row, template };
                        }
                        return row;
                    });
                };

                const isProcessActorsEnabled = () => actorsEnabledInput?.checked === true;

                const syncActorsEnabledLabel = () => {
                    const enabled = isProcessActorsEnabled();
                    if (!actorsEnabledLabel) return;
                    actorsEnabledLabel.textContent = enabled ? 'เปิด' : 'ปิด';
                    actorsEnabledLabel.classList.toggle('text-emerald-700', enabled);
                    actorsEnabledLabel.classList.toggle('text-slate-500', !enabled);
                };

                const isParagraphEnabled = (key) => paragraphEnabled[key] !== false;

                const syncParagraphCard = (key) => {
                    const card = document.querySelector(`[data-paragraph-card="${key}"]`);
                    if (!card) return;
                    const enabled = isParagraphEnabled(key);
                    const label = card.querySelector('.memo-paragraph-enabled-label');
                    const input = card.querySelector('.memo-paragraph-enabled');
                    const textarea = card.querySelector('.memo-paragraph-input');
                    if (input) input.checked = enabled;
                    if (label) {
                        label.textContent = enabled ? 'เปิด' : 'ปิด';
                        label.classList.toggle('text-emerald-700', enabled);
                        label.classList.toggle('text-slate-500', !enabled);
                    }
                    card.classList.toggle('opacity-60', !enabled);
                    if (textarea) {
                        textarea.disabled = !enabled;
                        textarea.classList.toggle('bg-slate-100', !enabled);
                        textarea.classList.toggle('text-slate-400', !enabled);
                    }
                };

                const syncParagraphCards = () => {
                    paragraphKeys.forEach((key) => syncParagraphCard(key));
                };

                const renderBlockOrderPreview = () => {
                    if (!blockOrderPreview) return;
                    const visibleOrder = blockOrder.filter((key) => key === 'items' || isParagraphEnabled(key));
                    blockOrderPreview.innerHTML = visibleOrder.map((key, index) => {
                        const label = blockLabels[key] || key;
                        const isItems = key === 'items';
                        return `<span class="inline-flex items-center gap-1 rounded-md px-2 py-1 ${isItems ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-200' : 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'}">
                            <span class="font-bold opacity-60">${index + 1}</span>${escapeHtml(label)}
                        </span>${index < visibleOrder.length - 1 ? '<i class="fas fa-chevron-right text-[8px] text-amber-400/80"></i>' : ''}`;
                    }).join('');
                };

                const renderTree = () => {
                    if (!treeEl) return;
                    const groups = {};
                    (catalog || []).forEach((row) => {
                        const kind = String(row.kind || '');
                        if (!groups[kind]) {
                            groups[kind] = { label: String(row.kind_label || kind), items: [] };
                        }
                        groups[kind].items.push(row);
                    });

                    treeEl.innerHTML = Object.keys(groups).map((kind) => {
                        const group = groups[kind];
                        const icon = kindIcons[kind] || 'fa-folder';
                        const itemsHtml = group.items.map((item) => {
                            const active = selectedKind === kind && selectedSubType === String(item.sub_type);
                            const hasTemplate = item?.template && String(item.template.subject || '').trim() !== '';
                            return `<button type="button" data-kind="${kind}" data-sub-type="${String(item.sub_type)}"
                                class="group flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm transition ${active
                                    ? 'bg-gradient-to-r from-emerald-600 to-teal-600 font-semibold text-white shadow-md shadow-emerald-500/20'
                                    : 'text-slate-700 hover:bg-slate-50'}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-white'}">
                                    <i class="fas fa-file-alt text-xs"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate">${escapeHtml(item.sub_type_label || item.sub_type)}</span>
                                    ${hasTemplate ? `<span class="block truncate text-[10px] ${active ? 'text-emerald-100' : 'text-slate-400'}">มีเทมเพลตแล้ว</span>` : `<span class="block text-[10px] ${active ? 'text-emerald-100/90' : 'text-amber-600'}">ยังไม่ตั้งค่า</span>`}
                                </span>
                                ${active ? '<i class="fas fa-check text-xs opacity-90"></i>' : '<i class="fas fa-chevron-right text-[10px] text-slate-300"></i>'}
                            </button>`;
                        }).join('');

                        return `<div class="overflow-hidden rounded-xl border border-slate-200/90 bg-slate-50/50">
                            <div class="flex items-center gap-2 border-b border-slate-200/80 bg-white px-3 py-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                    <i class="fas ${icon} text-[11px]"></i>
                                </span>
                                <span class="text-xs font-bold text-slate-800">${escapeHtml(group.label)}</span>
                            </div>
                            <div class="space-y-1 p-2">${itemsHtml}</div>
                        </div>`;
                    }).join('');

                    treeEl.querySelectorAll('button[data-kind]').forEach((button) => {
                        button.addEventListener('click', () => {
                            selectedKind = String(button.dataset.kind || 'general');
                            selectedSubType = String(button.dataset.subType || 'default');
                            loadTemplateIntoForm();
                            renderTree();
                        });
                    });
                };

                const renderBlockOrder = () => {
                    if (!blockOrderList) return;
                    blockOrderList.innerHTML = blockOrder.map((key, index) => {
                        const label = blockLabels[key] || key;
                        const isItems = key === 'items';
                        const faIcon = blockIcons[key] || 'fa-grip-lines';
                        const paragraphOff = paragraphKeys.includes(key) && !isParagraphEnabled(key);
                        return `<li class="flex items-center gap-2 rounded-xl border ${paragraphOff ? 'border-slate-200 bg-slate-50 opacity-60' : (isItems ? 'border-amber-200 bg-amber-50/50' : 'border-slate-200 bg-white')} px-3 py-2.5 shadow-sm" data-block-key="${key}">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${isItems ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'}">
                                <i class="fas ${faIcon} text-xs"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="block text-xs font-bold text-slate-800">${escapeHtml(label)}</span>
                                <span class="text-[10px] text-slate-400">${paragraphOff ? 'ปิดใช้งาน' : `ลำดับที่ ${index + 1}`}</span>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <button type="button" data-move="up" data-index="${index}" title="เลื่อนขึ้น"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-30"
                                    ${index === 0 ? 'disabled' : ''}>
                                    <i class="fas fa-chevron-up text-xs"></i>
                                </button>
                                <button type="button" data-move="down" data-index="${index}" title="เลื่อนลง"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-30"
                                    ${index === blockOrder.length - 1 ? 'disabled' : ''}>
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </button>
                            </div>
                        </li>`;
                    }).join('');

                    blockOrderList.querySelectorAll('button[data-move]').forEach((button) => {
                        button.addEventListener('click', () => {
                            const index = Number(button.dataset.index || 0);
                            const direction = String(button.dataset.move || '');
                            const target = direction === 'up' ? index - 1 : index + 1;
                            if (target < 0 || target >= blockOrder.length) return;
                            const copy = [...blockOrder];
                            [copy[index], copy[target]] = [copy[target], copy[index]];
                            blockOrder = copy;
                            renderBlockOrder();
                            renderBlockOrderPreview();
                        });
                    });
                    renderBlockOrderPreview();
                };

                const renderApprovalSteps = (selectedCodes = []) => {
                    if (!approvalStepsEl) return;
                    const selectedSet = new Set((selectedCodes || []).map((code) => String(code)));
                    approvalStepsEl.innerHTML = (approvalStepOptions || []).map((step) => {
                        const code = String(step.code || '');
                        const checked = selectedSet.has(code);
                        return `<label class="group relative flex cursor-pointer items-start gap-3 rounded-xl border px-3 py-3 transition ${checked
                            ? 'border-emerald-300 bg-emerald-50/80 ring-1 ring-emerald-200'
                            : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'}">
                            <input type="checkbox" class="memo-approval-step peer sr-only" value="${escapeHtml(code)}" ${checked ? 'checked' : ''} />
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-md border-2 transition peer-checked:border-emerald-600 peer-checked:bg-emerald-600 ${checked ? 'border-emerald-600 bg-emerald-600' : 'border-slate-300 bg-white'}">
                                <i class="fas fa-check text-[10px] text-white ${checked ? '' : 'opacity-0'}"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-800">${escapeHtml(step.label || code)}</span>
                                <span class="mt-0.5 inline-block rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-500">${escapeHtml(code)}</span>
                            </span>
                        </label>`;
                    }).join('');

                    approvalStepsEl.querySelectorAll('.memo-approval-step').forEach((input) => {
                        input.addEventListener('change', () => {
                            const label = input.closest('label');
                            if (!label) return;
                            const on = input.checked;
                            label.classList.toggle('border-emerald-300', on);
                            label.classList.toggle('bg-emerald-50/80', on);
                            label.classList.toggle('ring-1', on);
                            label.classList.toggle('ring-emerald-200', on);
                            label.classList.toggle('border-slate-200', !on);
                            label.classList.toggle('bg-white', !on);
                            const box = label.querySelector('span.flex.h-5');
                            const icon = label.querySelector('.fa-check');
                            if (box) {
                                box.classList.toggle('border-emerald-600', on);
                                box.classList.toggle('bg-emerald-600', on);
                                box.classList.toggle('border-slate-300', !on);
                                box.classList.toggle('bg-white', !on);
                            }
                            if (icon) icon.classList.toggle('opacity-0', !on);
                        });
                    });
                };

                const loadTemplateIntoForm = () => {
                    const entry = findCatalogEntry(selectedKind, selectedSubType);
                    const template = entry?.template || null;
                    if (kindInput) kindInput.value = selectedKind;
                    if (subTypeInput) subTypeInput.value = selectedSubType;

                    const subLabel = String(entry?.sub_type_label || '');
                    const kindLabel = String(entry?.kind_label || '');

                    if (selectedLabel) {
                        selectedLabel.textContent = subLabel || '-';
                    }
                    if (kindBadge) {
                        kindBadge.textContent = kindLabel || selectedKind;
                    }

                    document.getElementById('memo-name').value = String(template?.name || entry?.sub_type_label || '');
                    document.getElementById('memo-subject').value = String(template?.subject || '');
                    document.getElementById('memo-to').value = String(template?.to || '');
                    document.getElementById('memo-paragraph1').value = String(template?.paragraph1 || '');
                    document.getElementById('memo-paragraph2').value = String(template?.paragraph2 || '');
                    document.getElementById('memo-paragraph3').value = String(template?.paragraph3 || '');
                    document.getElementById('memo-paragraph4').value = String(template?.paragraph4 || '');
                    paragraphEnabled = {
                        paragraph1: true,
                        paragraph2: true,
                        paragraph3: true,
                        paragraph4: true,
                        ...(template?.paragraph_enabled && typeof template.paragraph_enabled === 'object' ? template.paragraph_enabled : {}),
                    };
                    paragraphKeys.forEach((key) => {
                        paragraphEnabled[key] = paragraphEnabled[key] !== false && paragraphEnabled[key] !== 0 && paragraphEnabled[key] !== '0';
                    });
                    syncParagraphCards();

                    blockOrder = Array.isArray(template?.block_order) && template.block_order.length > 0
                        ? [...template.block_order]
                        : ['paragraph1', 'paragraph2', 'items', 'paragraph3', 'paragraph4'];
                    renderBlockOrder();
                    renderApprovalSteps(template?.approval_step_codes || []);

                    if (actorsEnabledInput) {
                        actorsEnabledInput.checked = template?.process_actors_enabled === true;
                    }
                    syncActorsEnabledLabel();
                };

                actorsEnabledInput?.addEventListener('change', syncActorsEnabledLabel);

                document.querySelectorAll('.memo-paragraph-enabled').forEach((input) => {
                    input.addEventListener('change', () => {
                        const key = String(input.dataset.paragraphKey || '');
                        if (!paragraphKeys.includes(key)) return;
                        paragraphEnabled[key] = input.checked === true;
                        syncParagraphCard(key);
                        renderBlockOrder();
                    });
                });

                form?.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (saveButton) {
                        saveButton.disabled = true;
                        saveButton.classList.add('opacity-70');
                    }

                    const stepOrderIndex = new Map(
                        (approvalStepOptions || []).map((step, index) => [String(step.code || ''), index])
                    );
                    const approvalCodes = Array.from(document.querySelectorAll('.memo-approval-step:checked'))
                        .map((input) => String(input.value || '').trim())
                        .filter(Boolean)
                        .sort((a, b) => (stepOrderIndex.get(a) ?? 999) - (stepOrderIndex.get(b) ?? 999));
                    const payload = {
                        kind: selectedKind,
                        sub_type: selectedSubType,
                        name: String(document.getElementById('memo-name')?.value || '').trim(),
                        subject: String(document.getElementById('memo-subject')?.value || '').trim(),
                        to: String(document.getElementById('memo-to')?.value || '').trim(),
                        paragraph1: String(document.getElementById('memo-paragraph1')?.value || '').trim(),
                        paragraph2: String(document.getElementById('memo-paragraph2')?.value || '').trim(),
                        paragraph3: String(document.getElementById('memo-paragraph3')?.value || '').trim(),
                        paragraph4: String(document.getElementById('memo-paragraph4')?.value || '').trim(),
                        paragraph_enabled: {
                            paragraph1: isParagraphEnabled('paragraph1'),
                            paragraph2: isParagraphEnabled('paragraph2'),
                            paragraph3: isParagraphEnabled('paragraph3'),
                            paragraph4: isParagraphEnabled('paragraph4'),
                        },
                        block_order: blockOrder,
                        approval_step_codes: approvalCodes,
                        process_actors_enabled: isProcessActorsEnabled() ? 1 : 0,
                    };

                    try {
                        const response = await fetch(`${apiBase}/memo-templates/save`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf(),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });
                        const result = await response.json();
                        if (result?.status === 'success') {
                            updateCatalogTemplate(selectedKind, selectedSubType, result.data || null);
                            renderTree();
                            window.toast?.success?.('บันทึกเทมเพลตสำเร็จ', { timer: 1200 });
                        } else {
                            window.toast?.error?.(result?.message || 'บันทึกไม่สำเร็จ', { timer: 1400 });
                        }
                    } catch (error) {
                        window.toast?.error?.('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', { timer: 1400 });
                    } finally {
                        if (saveButton) {
                            saveButton.disabled = false;
                            saveButton.classList.remove('opacity-70');
                        }
                    }
                });

                renderTree();
                loadTemplateIntoForm();
            });
        </script>
    @endpush
</x-layouts.app>
