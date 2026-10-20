<?php echo $__env->make('withdraw.partials.detail-mobile-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php if (! $__env->hasRenderedOnce('0d6cfed4-f14d-432a-b93c-55946d7a47da')): $__env->markAsRenderedOnce('0d6cfed4-f14d-432a-b93c-55946d7a47da'); ?>
    <?php $__env->startPush('scripts'); ?>
        <script>
            window.WithdrawDocumentPreview = window.WithdrawDocumentPreview || (function () {
                const DESKTOP_PREVIEW_MQ = '(min-width: 1024px)';
                const boundPreviews = [];
                const escapeHtml = (unsafe) => String(unsafe ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
                }[char]));
                const canEmbedPdfPreview = () => {
                    if (typeof window.matchMedia !== 'function') {
                        return true;
                    }
                    const isWideScreen = window.matchMedia('(min-width: 1024px)').matches;
                    const isFinePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
                    return isWideScreen && isFinePointer;
                };
                const guessKind = (file = {}) => {
                    const mime = String(file?.mime || '').trim().toLowerCase();
                    const name = String(file?.name || '').trim().toLowerCase();
                    const url = String(file?.url || '').trim().toLowerCase();
                    const haystack = `${mime} ${name} ${url}`;
                    if (mime.includes('pdf') || /\.pdf(\?|$)/.test(haystack)) return 'pdf';
                    if (mime.startsWith('image/') || /\.(png|jpe?g|gif|webp|bmp|svg)(\?|$)/.test(haystack)) return 'image';
                    if (
                        mime.includes('spreadsheet')
                        || mime.includes('excel')
                        || mime === 'text/csv'
                        || /\.(xlsx|xls|csv)(\?|$)/.test(haystack)
                    ) return 'spreadsheet';
                    if (
                        mime.includes('word')
                        || mime.includes('msword')
                        || /\.(docx|doc)(\?|$)/.test(haystack)
                    ) return 'word';
                    return 'other';
                };
                const withDownloadParam = (url) => {
                    const source = String(url || '').trim();
                    if (source === '') return '';
                    try {
                        const parsed = new URL(source, window.location.origin);
                        parsed.searchParams.set('download', '1');
                        return parsed.toString();
                    } catch (_error) {
                        return source + (source.includes('?') ? '&' : '?') + 'download=1';
                    }
                };
                const fileKindMeta = (kind) => {
                    if (kind === 'pdf') {
                        return {
                            icon: 'fa-file-pdf',
                            iconWrap: 'bg-rose-50 text-rose-700 ring-rose-100',
                            title: 'ไฟล์ PDF',
                            hint: 'เปิดไฟล์เพื่อดูบนอุปกรณ์นี้',
                        };
                    }
                    if (kind === 'spreadsheet') {
                        return {
                            icon: 'fa-file-excel',
                            iconWrap: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                            title: 'ไฟล์ Excel',
                            hint: 'ไฟล์ Excel ไม่สามารถแสดงในหน้าเว็บได้ กรุณาเปิดหรือดาวน์โหลดเพื่อดู',
                        };
                    }
                    if (kind === 'word') {
                        return {
                            icon: 'fa-file-word',
                            iconWrap: 'bg-sky-50 text-sky-700 ring-sky-100',
                            title: 'ไฟล์ Word',
                            hint: 'ไฟล์ Word ไม่สามารถแสดงในหน้าเว็บได้ กรุณาเปิดหรือดาวน์โหลดเพื่อดู',
                        };
                    }
                    return {
                        icon: 'fa-file',
                        iconWrap: 'bg-slate-100 text-slate-600 ring-slate-200',
                        title: 'ไฟล์แนบ',
                        hint: 'ไฟล์ประเภทนี้ไม่สามารถแสดงในหน้าเว็บได้ กรุณาเปิดหรือดาวน์โหลดเพื่อดู',
                    };
                };
                const renderDownloadCard = (file = {}, label = '', kind = 'other') => {
                    const url = String(file?.url || '').trim();
                    const name = String(file?.name || '').trim() || 'เอกสาร';
                    const meta = fileKindMeta(kind);
                    const downloadUrl = withDownloadParam(url);
                    return `<div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-3">
                        <div class="mb-2 text-sm font-semibold text-slate-800 max-lg:leading-snug">${escapeHtml(label)}${name !== '' ? ` <span class="font-normal text-slate-500 max-lg:mt-0.5 max-lg:block max-lg:break-all">(${escapeHtml(name)})</span>` : ''}</div>
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-3 max-lg:flex-col max-lg:items-stretch">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ring-1 ${meta.iconWrap}">
                                    <i class="fas ${meta.icon}"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-800 max-lg:break-all max-lg:whitespace-normal">${escapeHtml(name)}</div>
                                    <div class="text-xs text-slate-500 max-lg:leading-relaxed">${escapeHtml(meta.hint)}</div>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2 max-lg:w-full max-lg:flex-col">
                                <a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-xs font-semibold text-sky-700 shadow-sm transition hover:bg-sky-100 max-lg:w-full max-lg:justify-center max-lg:py-2">เปิดไฟล์</a>
                                <a href="${escapeHtml(downloadUrl)}" download="${escapeHtml(name)}" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100 max-lg:w-full max-lg:justify-center max-lg:py-2">ดาวน์โหลด</a>
                            </div>
                        </div>
                    </div>`;
                };
                const isSameOriginUrl = (url) => {
                    try {
                        return new URL(url, window.location.origin).origin === window.location.origin;
                    } catch (_error) {
                        return false;
                    }
                };
                const renderFile = (file = {}, label = '') => {
                    const url = String(file?.url || '').trim();
                    const name = String(file?.name || '').trim() || 'เอกสาร';
                    const canEmbed = file?.embed !== false && isSameOriginUrl(url);
                    const caption = `<div class="mb-2 flex flex-wrap items-center justify-between gap-2 max-lg:flex-col max-lg:items-stretch">
                        <div class="text-sm font-semibold text-slate-800 max-lg:min-w-0 max-lg:leading-snug">${escapeHtml(label)}${name !== '' ? ` <span class="font-normal text-slate-500 max-lg:mt-0.5 max-lg:block max-lg:break-all">(${escapeHtml(name)})</span>` : ''}</div>
                        ${url !== '' ? `<a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 shadow-sm transition hover:bg-sky-100 max-lg:w-full max-lg:justify-center max-lg:py-2">เปิดไฟล์</a>` : ''}
                    </div>`;
                    if (url === '') {
                        return `<div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">${escapeHtml(label)} — ยังไม่มีไฟล์</div>`;
                    }
                    const kind = guessKind(file);
                    if (kind === 'image') {
                        if (!canEmbed) {
                            return renderDownloadCard(file, label, 'other');
                        }
                        return `<div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-2">${caption}<img src="${escapeHtml(url)}" alt="${escapeHtml(name)}" class="max-h-[70vh] w-full rounded-lg bg-slate-50 object-contain max-lg:max-h-[50vh]" /></div>`;
                    }
                    if (kind === 'pdf') {
                        if (!canEmbed || !canEmbedPdfPreview()) {
                            return renderDownloadCard(file, label, 'pdf');
                        }
                        return `<div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-2">${caption}<iframe src="${escapeHtml(url)}" title="${escapeHtml(name)}" class="h-[70vh] w-full rounded-lg border border-slate-200 bg-white"></iframe></div>`;
                    }
                    return renderDownloadCard(file, label, kind);
                };
                const renderHtml = (detail = {}, options = {}) => {
                    const projectDocsLast = options.projectDocsLast === true;
                    const docs = Array.isArray(detail.documents) ? detail.documents : [];
                    const uploadedFiles = Array.isArray(detail.uploaded_files) ? detail.uploaded_files : [];
                    const uploadedMap = new Map();
                    uploadedFiles.forEach((file) => {
                        const key = `${String(file.type || '').trim()}::${String(file.item || '').trim()}`;
                        if (!uploadedMap.has(key)) uploadedMap.set(key, []);
                        uploadedMap.get(key).push(file);
                    });
                    const projectMemo = detail.project_approve_memo_pdf && typeof detail.project_approve_memo_pdf === 'object'
                        ? detail.project_approve_memo_pdf
                        : null;
                    const projectMemoUrl = String(projectMemo?.url || '').trim();
                    const projectPrint = detail.project_print_pdf && typeof detail.project_print_pdf === 'object'
                        ? detail.project_print_pdf
                        : null;
                    const projectPrintUrl = String(projectPrint?.url || '').trim();
                    const isProjectVirtualDoc = (type) => ['project_approve_memo', 'project_print'].includes(String(type || '').trim());
                    const projectEntries = [];
                    if (projectMemoUrl !== '') {
                        projectEntries.push({
                            label: 'บันทึกอนุมัติโครงการ',
                            files: [{
                                url: projectMemoUrl,
                                name: String(projectMemo?.name || 'บันทึกอนุมัติโครงการ.pdf').trim() || 'บันทึกอนุมัติโครงการ.pdf',
                                mime: String(projectMemo?.mime || 'application/pdf').trim() || 'application/pdf',
                            }],
                        });
                    }
                    if (projectPrintUrl !== '') {
                        projectEntries.push({
                            label: 'รายละเอียดโครงการ',
                            files: [{
                                url: projectPrintUrl,
                                name: String(projectPrint?.name || 'รายละเอียดโครงการ.pdf').trim() || 'รายละเอียดโครงการ.pdf',
                                mime: String(projectPrint?.mime || 'application/pdf').trim() || 'application/pdf',
                            }],
                        });
                    }
                    const attachmentEntries = [];
                    if (docs.length > 0) {
                        docs.forEach((doc) => {
                            if (isProjectVirtualDoc(doc.type)) {
                                return;
                            }
                            const files = uploadedMap.get(`${String(doc.type || '').trim()}::${String(doc.item_id || '').trim()}`) || [];
                            attachmentEntries.push({
                                label: String(doc.label || '-').trim() || '-',
                                files,
                            });
                        });
                    } else {
                        uploadedFiles.forEach((file) => {
                            if (isProjectVirtualDoc(file.type)) {
                                return;
                            }
                            attachmentEntries.push({
                                label: '',
                                files: [file],
                            });
                        });
                    }
                    (Array.isArray(detail.travel_supporting_files) ? detail.travel_supporting_files : []).forEach((file) => {
                        const url = String(file?.url || '').trim();
                        if (url === '') return;
                        attachmentEntries.push({
                            label: String(file?.label || file?.name || 'เอกสารจากระบบขออนุมัติไปปฏิบัติงาน').trim() || 'เอกสารจากระบบขออนุมัติไปปฏิบัติงาน',
                            files: [file],
                        });
                    });
                    const entries = projectDocsLast
                        ? [...attachmentEntries, ...projectEntries]
                        : [...projectEntries, ...attachmentEntries];
                    const blocks = [];
                    let labelNumber = 1;
                    entries.forEach((entry) => {
                        const label = entry.label !== '' ? `${labelNumber}) ${entry.label}` : `${labelNumber})`;
                        labelNumber += 1;
                        if (!Array.isArray(entry.files) || entry.files.length === 0) {
                            blocks.push(`<div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">${escapeHtml(label)} — ยังไม่มีไฟล์</div>`);
                            return;
                        }
                        entry.files.forEach((file) => blocks.push(renderFile(file, label)));
                    });
                    if (blocks.length === 0) {
                        return '<div class="text-slate-400">- ไม่มีเอกสารประกอบ -</div>';
                    }
                    return `<div class="space-y-3">${blocks.join('')}</div>`;
                };
                const paint = (element, detail = {}, options = {}) => {
                    if (!element) return;
                    element.classList.remove('space-y-1');
                    element.classList.add('space-y-3');
                    element.innerHTML = renderHtml(detail, options);
                };
                const fill = (element, detail = {}, options = {}) => {
                    if (!element) return;
                    const nextBound = boundPreviews.filter((item) => item.element !== element && document.contains(item.element));
                    nextBound.push({ element, detail, options });
                    boundPreviews.splice(0, boundPreviews.length, ...nextBound);
                    paint(element, detail, options);
                };
                if (typeof window.matchMedia === 'function' && !window.__withdrawPdfPreviewMqBound) {
                    window.__withdrawPdfPreviewMqBound = true;
                    const media = window.matchMedia(DESKTOP_PREVIEW_MQ);
                    const onViewportChange = () => {
                        boundPreviews.splice(0, boundPreviews.length, ...boundPreviews.filter((item) => item.element && document.contains(item.element)));
                        boundPreviews.forEach((item) => paint(item.element, item.detail, item.options || {}));
                    };
                    if (typeof media.addEventListener === 'function') {
                        media.addEventListener('change', onViewportChange);
                    } else if (typeof media.addListener === 'function') {
                        media.addListener(onViewportChange);
                    }
                }
                return { fill, renderHtml };
            })();
        </script>
    <?php $__env->stopPush(); ?>
<?php endif; ?>
<?php /**PATH /var/www/application/withdraw/resources/views/withdraw/partials/document-preview-scripts.blade.php ENDPATH**/ ?>