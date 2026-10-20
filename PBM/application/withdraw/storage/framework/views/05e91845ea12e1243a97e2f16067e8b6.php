<?php if(empty($isReviewerApprovalMode)): ?>
    
    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'withdraw-procurement-item-modal','maxWidth' => 'lg','static' => true,'variant' => 'modern','title' => 'เลือกรายการเบิกจ่าย','description' => 'เลือกรายการที่จะจัดทำเอกสารจัดซื้อจัดจ้างของรายการเบิกจ่ายนี้','iconClass' => 'fas fa-list']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'withdraw-procurement-item-modal','maxWidth' => 'lg','static' => true,'variant' => 'modern','title' => 'เลือกรายการเบิกจ่าย','description' => 'เลือกรายการที่จะจัดทำเอกสารจัดซื้อจัดจ้างของรายการเบิกจ่ายนี้','iconClass' => 'fas fa-list']); ?>
        <div class="space-y-3 p-1">
            <div id="procurement-item-picker-empty" class="hidden rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                ไม่พบรายการเบิกจ่ายในเอกสารนี้
            </div>
            <div id="procurement-item-picker-list" class="space-y-2"></div>
        </div>
        <div class="mt-4 flex justify-end gap-2 border-t border-slate-100 pt-4">
            <button type="button" id="procurement-item-picker-skip"
                class="hidden rounded-xl border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700 shadow-sm transition hover:bg-sky-100">
                ดำเนินการต่อโดยไม่ระบุรายการ
            </button>
            <button type="button" @click="$dispatch('close-modal', 'withdraw-procurement-item-modal')"
                class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                ปิด
            </button>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'withdraw-procurement-type-modal','maxWidth' => 'md','static' => true,'variant' => 'modern','title' => 'เอกสารจัดซื้อจัดจ้าง','description' => 'เลือกประเภทเอกสารเพื่อกรอกข้อมูลและบันทึก','iconClass' => 'fas fa-file-invoice']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'withdraw-procurement-type-modal','maxWidth' => 'md','static' => true,'variant' => 'modern','title' => 'เอกสารจัดซื้อจัดจ้าง','description' => 'เลือกประเภทเอกสารเพื่อกรอกข้อมูลและบันทึก','iconClass' => 'fas fa-file-invoice']); ?>
        <div class="space-y-3 p-1">
            <div id="procurement-type-selected-item" class="hidden rounded-xl border border-sky-200 bg-sky-50/70 px-4 py-2.5 text-sm text-sky-900">
                รายการเบิกจ่ายที่เลือก:
                <span id="procurement-type-selected-item-label" class="font-semibold"></span>
                <button type="button" id="procurement-type-change-item"
                    class="ml-2 text-xs font-semibold text-sky-700 underline decoration-sky-300 underline-offset-2 hover:text-sky-900">
                    เปลี่ยนรายการ
                </button>
            </div>
            <div id="procurement-type-eight-only-hint" class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900">
                รายการเบิกจ่ายนี้มีจำนวนเงินเกิน 10,000 บาท จึงใช้ได้เฉพาะเอกสารจัดซื้อจัดจ้าง (8 ข้อ)
            </div>
            <button type="button" data-procurement-type-option="w119"
                @click.stop="
                    $dispatch('close-modal', 'withdraw-procurement-type-modal');
                    $dispatch('open-modal', 'withdraw-procurement-form-modal');
                    window.WithdrawProjectProcurement && window.WithdrawProjectProcurement.openForm('w119');
                "
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left transition hover:border-sky-300 hover:bg-sky-50/60">
                <div class="text-sm font-semibold text-slate-800">เอกสารจัดซื้อจัดจ้าง (ว.119)</div>
                <div class="mt-0.5 text-xs text-slate-500">รายงานขอความเห็นชอบตามหนังสือ กค (กวจ) 0405.2/ว 119</div>
            </button>
            <button type="button" data-procurement-type-option="eight"
                @click.stop="
                    $dispatch('close-modal', 'withdraw-procurement-type-modal');
                    $dispatch('open-modal', 'withdraw-procurement-form-modal');
                    window.WithdrawProjectProcurement && window.WithdrawProjectProcurement.openForm('eight');
                "
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left transition hover:border-sky-300 hover:bg-sky-50/60">
                <div class="text-sm font-semibold text-slate-800">เอกสารจัดซื้อจัดจ้าง (8 ข้อ)</div>
                <div class="mt-0.5 text-xs text-slate-500">รายงานขอความเห็นชอบการจัดซื้อหรือจัดจ้างโดยวิธีเฉพาะเจาะจง</div>
            </button>
        </div>
        <div class="mt-4 flex justify-end border-t border-slate-100 pt-4">
            <button type="button" @click="$dispatch('close-modal', 'withdraw-procurement-type-modal')"
                class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                ปิด
            </button>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'withdraw-procurement-form-modal','maxWidth' => '6xl','static' => true,'variant' => 'modern','title' => 'กรอกเอกสารจัดซื้อจัดจ้าง','description' => 'กรอกข้อมูลที่จำเป็นแล้วบันทึกเข้าฐานข้อมูล','iconClass' => 'fas fa-file-signature','dialogClass' => 'max-h-[90vh] overflow-y-auto']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'withdraw-procurement-form-modal','maxWidth' => '6xl','static' => true,'variant' => 'modern','title' => 'กรอกเอกสารจัดซื้อจัดจ้าง','description' => 'กรอกข้อมูลที่จำเป็นแล้วบันทึกเข้าฐานข้อมูล','iconClass' => 'fas fa-file-signature','dialogClass' => 'max-h-[90vh] overflow-y-auto']); ?>
        <form id="withdraw-procurement-form" class="space-y-4">
            <input type="hidden" id="procurement-type" value="w119" />
            <input type="hidden" id="procurement-withdraw-id" value="" />
            <input type="hidden" id="procurement-id" value="" />
            <input type="hidden" id="procurement-item-id" value="" />
            <div class="rounded-xl border border-sky-200 bg-sky-50/70 px-4 py-2.5 text-sm text-sky-900">
                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                    <span>ประเภทที่เลือก:</span>
                    <span id="procurement-type-label" class="font-semibold">เอกสารจัดซื้อจัดจ้าง (ว.119)</span>
                </div>
                <div id="procurement-form-selected-item" class="mt-1 hidden text-xs">
                    รายการเบิกจ่าย: <span id="procurement-form-selected-item-label" class="font-semibold"></span>
                </div>
                <p id="procurement-form-hint" class="mt-1 text-xs text-sky-800/80">กรอกเฉพาะรายละเอียดประกอบรายงาน ข้อมูลอื่นดึงจากรายการเบิกจ่าย และการอนุมัติเป็นไปตามเส้นทางของรายการเบิกจ่าย</p>
            </div>

            <div id="procurement-eight-extra" class="hidden space-y-4">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-50/90 to-white px-4 py-3">
                        <h6 class="text-sm font-semibold text-slate-800">ข้อมูลเอกสารจัดซื้อจัดจ้าง (8 ข้อ)</h6>
                        <p class="mt-0.5 text-xs text-slate-500">กรอกข้อมูลเพิ่มเติมของเอกสาร 8 ข้อ จากนั้นกรอกรายการที่ขอซื้อหรือจ้างด้านล่าง</p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 p-4">
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">เหตุผลและความจำเป็นที่ต้องซื้อหรือจ้าง</label>
                            <textarea id="procurement-reason" rows="3" placeholder="ระบุเหตุผลและความจำเป็นที่ต้องซื้อหรือจ้าง"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-2 focus:ring-sky-100"></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">ผู้กำหนดคุณลักษณะเฉพาะของพัสดุ</label>
                            <?php if (isset($component)) { $__componentOriginal0728ba7b37b7eda62f62767c5dccebf3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-dropdown','data' => ['id' => 'procurement-inspector-staff','placeholder' => 'พิมพ์เพื่อค้นหาชื่อบุคลากร...','options' => $staffListOptions ?? [],'valueKey' => 'id','textKey' => 'label','showClear' => true,'clearTitle' => 'ล้างรายชื่อ']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'procurement-inspector-staff','placeholder' => 'พิมพ์เพื่อค้นหาชื่อบุคลากร...','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($staffListOptions ?? []),'valueKey' => 'id','textKey' => 'label','showClear' => true,'clearTitle' => 'ล้างรายชื่อ']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3)): ?>
<?php $attributes = $__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3; ?>
<?php unset($__attributesOriginal0728ba7b37b7eda62f62767c5dccebf3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0728ba7b37b7eda62f62767c5dccebf3)): ?>
<?php $component = $__componentOriginal0728ba7b37b7eda62f62767c5dccebf3; ?>
<?php unset($__componentOriginal0728ba7b37b7eda62f62767c5dccebf3); ?>
<?php endif; ?>
                            <input type="hidden" id="procurement-inspector" value="">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">รายละเอียดคุณลักษณะเฉพาะของพัสดุ (TOR)</label>
                            <textarea id="procurement-tor" rows="5" placeholder="ระบุรายละเอียดคุณลักษณะเฉพาะของพัสดุ หรือขอบเขตของงาน (TOR)"
                                class="w-full min-h-[6.5rem] rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-2 focus:ring-sky-100 resize-y"></textarea>
                        </div>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-700">ราคากลางของพัสดุที่จะซื้อหรือจ้าง</label>
                                <div class="flex items-center rounded-xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-100">
                                    <input id="procurement-median-price" type="number" min="0" step="0.01" placeholder="0.00"
                                        class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-right text-sm text-slate-800 shadow-none outline-none ring-0 placeholder:text-slate-400">
                                    <span class="pr-3 text-xs text-slate-500">บาท</span>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-700">วงเงินที่จะซื้อหรือจ้าง</label>
                                <div class="flex items-center rounded-xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-100">
                                    <input id="procurement-budget-amount" type="number" min="0" step="0.01" placeholder="0.00"
                                        class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-right text-sm text-slate-800 shadow-none outline-none ring-0 placeholder:text-slate-400">
                                    <span class="pr-3 text-xs text-slate-500">บาท</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">กำหนดวันที่ต้องใช้วัสดุหรือให้งานนั้นแล้วเสร็จ (ภายใน)</label>
                            <div class="flex max-w-xs items-center gap-2">
                                <input id="procurement-timeline-days" type="number" min="1" step="1" value="15"
                                    class="w-28 rounded-xl border border-slate-300 bg-white px-3 py-2 text-right text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-2 focus:ring-sky-100">
                                <span class="text-sm text-slate-600">วัน</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="procurement-header-fields" class="hidden rounded-2xl border border-slate-200 bg-white p-4">
                <h6 class="mb-3 text-sm font-semibold text-slate-800">หัวกระดาษ</h6>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-bold text-slate-700">ส่วนงาน</label>
                        <input id="procurement-department" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">โทร.</label>
                        <input id="procurement-phone" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">ที่</label>
                        <input id="procurement-document-number" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">วันที่</label>
                        <input id="procurement-document-date" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">เรื่อง</label>
                        <input id="procurement-subject" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">เรียน</label>
                        <input id="procurement-to" type="text" value="คณบดีคณะเภสัชศาสตร์" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-bold text-slate-700">รายการจัดซื้อจัดจ้าง</label>
                        <input id="procurement-item" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="เช่น จ้างเหมาบริการรถรับจ้าง พร้อมคนขับรถ">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-bold text-slate-700">วัตถุประสงค์ / โครงการ</label>
                        <textarea id="procurement-purpose" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">จำนวนเงิน (บาท)</label>
                        <input id="procurement-amount" type="number" min="0" step="0.01" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-bold text-slate-700">เบิกจ่ายจากงบประมาณ</label>
                        <textarea id="procurement-budget-source" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                    </div>
                </div>
            </div>

            <div id="procurement-w119-fields" class="space-y-4">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-sky-50/90 to-white px-4 py-3">
                        <div>
                            <h6 class="text-sm font-semibold text-slate-800">รายละเอียดประกอบรายงาน</h6>
                            <p class="mt-0.5 text-xs text-slate-500">กรอกทีละรายการตามเอกสารหลักฐาน ยอดรวมต้องไม่เกินวงเงินของรายการเบิกจ่าย</p>
                        </div>
                        <button type="button" data-add-detail-row id="procurement-add-detail-row"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-sky-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-sky-700">
                            <i class="fas fa-plus"></i>
                            เพิ่มรายการ
                        </button>
                    </div>
                    <div class="space-y-3 p-4">
                        <div id="procurement-detail-empty" class="hidden rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="text-sm font-semibold text-slate-600">ยังไม่มีรายการจัดซื้อจัดจ้าง</div>
                            <div class="mt-1 text-xs text-slate-500">กดปุ่มเพิ่มรายการ เพื่อกรอกเอกสารและแนบไฟล์</div>
                        </div>
                        <div id="procurement-detail-rows" class="space-y-3"></div>
                        <button type="button" data-add-detail-row
                            class="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/70 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">
                            <i class="fas fa-plus-circle"></i>
                            เพิ่มรายการจัดซื้อจัดจ้าง
                        </button>
                    </div>
                    <div id="procurement-detail-total-wrap" class="border-t border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <div class="text-[11px] font-medium text-slate-500">วงเงินรายการ</div>
                                <div class="text-sm font-semibold text-slate-800">
                                    <span id="procurement-detail-limit">0.00</span>
                                    <span class="text-xs font-normal text-slate-500">บาท</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-[11px] font-medium text-slate-500">รวมที่ใช้</div>
                                <div class="text-sm font-semibold text-slate-800">
                                    <span id="procurement-detail-total">0.00</span>
                                    <span class="text-xs font-normal text-slate-500">บาท</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-[11px] font-medium text-slate-500">คงเหลือ</div>
                                <div id="procurement-detail-remaining" class="text-sm font-semibold text-emerald-700">0.00 บาท</div>
                            </div>
                        </div>
                        <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-slate-200">
                            <div id="procurement-detail-progress" class="h-full w-0 rounded-full bg-sky-500 transition-all"></div>
                        </div>
                        <div id="procurement-detail-over-limit" class="mt-2 hidden rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600">
                            จำนวนเงินรวมเกินวงเงินของรายการเบิกจ่าย ไม่สามารถบันทึกได้
                        </div>
                    </div>
                </div>
                <div id="procurement-w119-announce" class="hidden rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h6 class="text-sm font-semibold text-slate-800">ประกาศผู้ชนะ</h6>
                        <button type="button" id="procurement-add-winner-row" class="rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">เพิ่มรายชื่อ</button>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-bold text-slate-700">เรื่องประกาศ</label>
                            <input id="procurement-announce-subject" type="text" value="รายชื่อผู้ชนะการคัดเลือกจัดซื้อจัดจ้าง โดยวิธีเฉพาะเจาะจง" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">วันที่ประกาศ</label>
                            <input id="procurement-announce-date" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-[520px] text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-2 py-2 text-left">ชื่อผู้ชนะ</th>
                                    <th class="px-2 py-2 text-right">จำนวนเงิน</th>
                                    <th class="px-2 py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="procurement-winner-rows"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="procurement-eight-fields" class="hidden space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h6 class="mb-3 text-sm font-semibold text-slate-800">รายการ 8 ข้อ</h6>
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">1. เหตุผลและความจำเป็น</label>
                            <textarea id="procurement-reason-hidden" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">2. ขอบเขตของงาน / คุณลักษณะเฉพาะ</label>
                            <input id="procurement-scope" type="text" value="เอกสารแนบ" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-700">3. ราคากลาง (บาท)</label>
                                <input id="procurement-median-price-hidden" type="number" min="0" step="0.01" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-700">4. วงเงินที่จะซื้อหรือจ้าง (บาท)</label>
                                <input id="procurement-budget-amount-hidden" type="number" min="0" step="0.01" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">5. กำหนดเวลาที่ต้องการใช้พัสดุ</label>
                            <input id="procurement-timeline" type="hidden" value="ภายใน 15 วัน">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">6. วิธีที่จะซื้อหรือจ้าง และเหตุผล</label>
                            <textarea id="procurement-method" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">จัดซื้อจัดจ้างโดยวิธีเฉพาะเจาะจง ตามมาตรา 56 (2) แห่งพระราชบัญญัติการจัดซื้อจัดจ้างและการบริหารพัสดุภาครัฐ พ.ศ. 2560 และข้อ 78 แห่งระเบียบกระทรวงการคลังว่าด้วยการจัดซื้อจัดจ้างและการบริหารพัสดุภาครัฐ พ.ศ. 2560</textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">7. หลักเกณฑ์การพิจารณาคัดเลือกข้อเสนอ</label>
                            <input id="procurement-criteria" type="text" value="ใช้เกณฑ์ราคา" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">8. ผู้กำหนดคุณลักษณะเฉพาะ / ผู้ตรวจรับพัสดุ</label>
                            <input id="procurement-inspector-hidden" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h6 class="text-sm font-semibold text-slate-800">รายการที่ขอซื้อหรือขอจ้าง</h6>
                        <button type="button" id="procurement-add-purchase-row" class="rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">เพิ่มรายการ</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[520px] text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-2 py-2 text-left">รายการ</th>
                                    <th class="px-2 py-2 text-left">จำนวน</th>
                                    <th class="px-2 py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="procurement-purchase-rows"></tbody>
                        </table>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h6 class="text-sm font-semibold text-slate-800">คุณลักษณะเฉพาะ / TOR</h6>
                        <button type="button" id="procurement-add-spec-row" class="rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">เพิ่มรายการ</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[620px] text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-2 py-2 text-left">รายการ</th>
                                    <th class="px-2 py-2 text-left">รายละเอียดเพิ่มเติม</th>
                                    <th class="px-2 py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="procurement-spec-rows"></tbody>
                        </table>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h6 class="mb-3 text-sm font-semibold text-slate-800">แบบ บก.06</h6>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-bold text-slate-700">ชื่อโครงการ</label>
                            <input id="procurement-bk-project" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">หน่วยงานเจ้าของโครงการ</label>
                            <input id="procurement-bk-owner" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">วงเงินงบประมาณที่ได้รับจัดสรร</label>
                            <input id="procurement-allocated-budget" type="number" min="0" step="0.01" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">วันที่กำหนดราคากลาง</label>
                            <input id="procurement-price-date" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-bold text-slate-700">แหล่งที่มาของราคากลาง</label>
                            <select id="procurement-price-source" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <option value="market">สืบราคาจากท้องตลาด</option>
                                <option value="committee">คำนวณตามหลักเกณฑ์คณะกรรมการราคากลาง</option>
                                <option value="cgd">ฐานข้อมูลราคาอ้างอิงกรมบัญชีกลาง</option>
                                <option value="budget_office">สำนักงบประมาณหรือหน่วยงานกลาง</option>
                                <option value="previous">ราคาที่เคยซื้อหรือจ้างครั้งล่าสุด</option>
                                <option value="other">ราคาอื่นตามหลักเกณฑ์ของหน่วยงาน</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-bold text-slate-700">รายละเอียดแหล่งราคา (เช่น ชื่อบริษัทที่สืบราคา)</label>
                            <input id="procurement-price-source-detail" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-bold text-slate-700">รายชื่อเจ้าหน้าที่ผู้กำหนดราคากลาง</label>
                            <input id="procurement-price-officer" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700">รายการราคากลาง</label>
                            <button type="button" id="procurement-add-price-row" class="rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">เพิ่มรายการ</button>
                        </div>
                        <div id="procurement-price-rows" class="space-y-2"></div>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h6 class="text-sm font-semibold text-slate-800">รายงานผลการพิจารณา</h6>
                        <button type="button" id="procurement-add-consider-row" class="rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">เพิ่มรายการ</button>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">ที่ (รายงานผลการพิจารณา)</label>
                            <input id="procurement-consider-number" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">วันที่รายงานผลการพิจารณา</label>
                            <input id="procurement-consider-date" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">อ้างอิงเลขที่รายงานขอความเห็นชอบ</label>
                            <input id="procurement-consider-ref-number" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">วันที่รายงานขอความเห็นชอบ</label>
                            <input id="procurement-consider-ref-date" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-bold text-slate-700">ผู้ชนะการคัดเลือก</label>
                            <input id="procurement-winner-name" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-[720px] text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-2 py-2 text-left">รายการพิจารณา</th>
                                    <th class="px-2 py-2 text-left">ผู้ชนะการเสนอราคา</th>
                                    <th class="px-2 py-2 text-right">ราคาที่เสนอ</th>
                                    <th class="px-2 py-2 text-right">ราคาที่ตกลงซื้อหรือจ้าง</th>
                                    <th class="px-2 py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="procurement-consider-rows"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="procurement-signer-fields" class="hidden rounded-2xl border border-slate-200 bg-white p-4">
                <h6 class="mb-3 text-sm font-semibold text-slate-800">ผู้ลงนาม</h6>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">ผู้เสนอรายงาน</label>
                        <input id="procurement-requester-name" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">ตำแหน่งผู้เสนอรายงาน</label>
                        <input id="procurement-requester-position" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">หัวหน้าเจ้าหน้าที่ / หัวหน้างานการเงิน</label>
                        <input id="procurement-finance-name" type="text" value="นางเรืองรอง คำปา" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">ตำแหน่งหัวหน้าเจ้าหน้าที่</label>
                        <input id="procurement-finance-position" type="text" value="หัวหน้างานการเงิน การคลังและพัสดุ" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">ผู้อนุมัติ</label>
                        <input id="procurement-approver-name" type="text" value="ผู้ช่วยศาสตราจารย์ ดร.ภญ.ดรุณี หงษ์วิเศษ" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">ตำแหน่งผู้อนุมัติ</label>
                        <input id="procurement-approver-position" type="text" value="รองคณบดี ปฏิบัติการแทน คณบดีคณะเภสัชศาสตร์" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" @click="$dispatch('close-modal', 'withdraw-procurement-form-modal')"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm">
                    ยกเลิก
                </button>
                <button type="submit" id="procurement-save-button"
                    class="rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md">
                    <i class="fas fa-save mr-1"></i>
                    บันทึก
                </button>
            </div>
        </form>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'withdraw-procurement-list-modal','maxWidth' => '3xl','static' => true,'variant' => 'modern','title' => 'รายการเอกสารจัดซื้อจัดจ้าง','description' => 'เปิดดู PDF หรือเพิ่ม/แก้ไขเอกสารของรายการเบิกจ่ายนี้','iconClass' => 'fas fa-folder-open']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'withdraw-procurement-list-modal','maxWidth' => '3xl','static' => true,'variant' => 'modern','title' => 'รายการเอกสารจัดซื้อจัดจ้าง','description' => 'เปิดดู PDF หรือเพิ่ม/แก้ไขเอกสารของรายการเบิกจ่ายนี้','iconClass' => 'fas fa-folder-open']); ?>
        <div class="space-y-3 p-1">
            <div id="procurement-list-summary"
                class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-orange-100 bg-orange-50/60 px-3.5 py-2.5">
                <div class="min-w-0 text-sm text-slate-700">
                    <span class="font-semibold text-slate-800">เอกสารทั้งหมด</span>
                    <span id="procurement-list-count" class="ml-1.5 inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-bold text-orange-800 ring-1 ring-orange-200">0</span>
                </div>
                <p class="text-xs text-slate-500">แตะปุ่ม PDF เพื่อเปิดดู · แก้ไขได้เมื่อรายการยังไม่เข้าขั้นตอนอนุมัติ</p>
            </div>
            <div id="procurement-list-locked-note" class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900">
                <div class="flex items-start gap-2.5">
                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <i class="fas fa-lock text-[11px]"></i>
                    </span>
                    <div>
                        <div class="font-semibold">ไม่สามารถเพิ่มหรือแก้ไขได้</div>
                        <div class="mt-0.5 text-amber-800/90">รายการนี้อยู่ในขั้นตอนดำเนินการแล้ว (ยกเว้นรายการที่ถูกส่งกลับมาให้แก้ไข)</div>
                    </div>
                </div>
            </div>
            <div id="procurement-list-empty" class="hidden rounded-xl border border-dashed border-orange-200 bg-orange-50/40 px-4 py-10 text-center">
                <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-orange-500 shadow-sm ring-1 ring-orange-100">
                    <i class="fas fa-file-invoice text-lg"></i>
                </div>
                <div class="text-sm font-semibold text-slate-700">ยังไม่มีเอกสารจัดซื้อจัดจ้าง</div>
                <p class="mt-1 text-xs text-slate-500">เริ่มสร้างเอกสารจากรายการเบิกจ่ายของโครงการนี้</p>
                <button type="button" id="procurement-list-empty-add"
                    class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:brightness-105">
                    <i class="fas fa-plus text-xs"></i>
                    เพิ่มเอกสารแรก
                </button>
            </div>
            <div id="procurement-list-wrap" class="max-h-[min(58vh,32rem)] space-y-2.5 overflow-y-auto pr-0.5">
                <div id="procurement-list-body" class="space-y-2.5"></div>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
            <p id="procurement-list-hint" class="text-xs text-slate-500">เลือกเอกสารเพื่อเปิดดู หรือเพิ่มเอกสารใหม่</p>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'withdraw-procurement-list-modal')"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                    ปิด
                </button>
                <button type="button" id="procurement-list-add"
                    class="rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:brightness-105">
                    <i class="fas fa-plus mr-1"></i>
                    เพิ่มเอกสาร
                </button>
            </div>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>

    <?php $__env->startPush('scripts'); ?>
    <script>
        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const saveApi = <?php echo json_encode(url('/withdraw/api/project/procurement'), 15, 512) ?>;
            const listApi = <?php echo json_encode(url('/withdraw/api/project/procurement'), 15, 512) ?>;
            const detailApiBase = <?php echo json_encode(url('/withdraw/api/general'), 15, 512) ?>;
            const pdfByIdBase = <?php echo json_encode(url('/withdraw/api/project/procurement'), 15, 512) ?>;
            const fileApiBase = <?php echo json_encode(url('/withdraw/api/project/procurement/file'), 15, 512) ?>;
            const winnersApi = <?php echo json_encode(url('/withdraw/api/project/procurement/winners'), 15, 512) ?>;
            const val = (id) => document.getElementById(id);
            const setVal = (id, value) => {
                const el = val(id);
                if (el) el.value = value == null ? '' : String(value);
            };
            let cachedDetail = null;
            let cachedDetailId = 0;
            let canEditProcurement = true;
            const lockedEditMessage = 'ไม่สามารถแก้ไขหรือเพิ่มเอกสารจัดซื้อจัดจ้างได้ เนื่องจากรายการนี้อยู่ในขั้นตอนดำเนินการแล้ว (แก้ไขได้เฉพาะรายการที่ถูกส่งกลับมาให้แก้ไข)';
            const isEditableWithdrawStatus = (status) => {
                const normalized = String(status || '').trim().toLowerCase();
                if (normalized === '') return true;
                return ['saved', 'draft', 'pending_revision'].includes(normalized);
            };
            const setProcurementEditMode = (canEdit) => {
                canEditProcurement = Boolean(canEdit);
                const addButton = val('procurement-list-add');
                const emptyAddButton = val('procurement-list-empty-add');
                const lockedNote = val('procurement-list-locked-note');
                addButton?.classList.toggle('hidden', !canEditProcurement);
                emptyAddButton?.classList.toggle('hidden', !canEditProcurement);
                lockedNote?.classList.toggle('hidden', canEditProcurement);
            };
            const todayIso = () => {
                const now = new Date();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                return `${now.getFullYear()}-${month}-${day}`;
            };
            const toIsoDate = (value) => {
                const raw = String(value || '').trim();
                if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw;
                const match = raw.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})$/);
                if (!match) return '';
                let year = Number(match[3]);
                if (year > 2400) year -= 543;
                if (year < 100) year += 2500;
                return `${year}-${String(match[2]).padStart(2, '0')}-${String(match[1]).padStart(2, '0')}`;
            };
            const collectRows = (tbodyId, fields) => Array.from(document.querySelectorAll(`#${tbodyId} tr`)).map((row) => {
                const item = {};
                fields.forEach((field) => {
                    item[field] = String(row.querySelector(`[data-field="${field}"]`)?.value || '').trim();
                });
                return item;
            });

            const isEightForm = () => String(val('procurement-type')?.value || '') === 'eight';

            const parseTimelineDays = (value) => {
                const match = String(value || '').match(/(\d+)/);
                const days = match ? Number(match[1]) : 15;
                return Number.isFinite(days) && days > 0 ? String(days) : '15';
            };

            const addDetailRow = (preset = {}) => {
                const list = val('procurement-detail-rows');
                if (!list) return;
                const eight = isEightForm();
                const fieldClass = 'w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-2 focus:ring-sky-100';
                const groupClass = 'flex items-center rounded-xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-100';
                const innerClass = 'min-w-0 flex-1 border-0 bg-transparent py-2 text-sm text-slate-800 shadow-none outline-none ring-0 placeholder:text-slate-400';
                const moneyInput = (field) => `
                    <div class="${groupClass}">
                        <input data-field="${field}" type="number" min="0" step="0.01" placeholder="0.00" class="${innerClass} px-3 text-right">
                        <span class="pr-3 text-xs text-slate-500">บาท</span>
                    </div>
                `;
                const fileBlock = (title) => `
                    <div class="md:col-span-2">
                        <div class="mb-1 text-xs font-bold text-slate-700">${title}</div>
                        <label data-file-drop class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/80 px-3 py-4 text-center transition hover:border-sky-300 hover:bg-sky-50/70">
                            <i class="fas fa-cloud-upload-alt text-lg text-sky-500"></i>
                            <span class="text-xs font-semibold text-slate-600">คลิกเพื่อแนบ หรือลากไฟล์มาวาง</span>
                            <span class="text-[11px] text-slate-400">PDF, JPG, PNG ไม่เกิน 10 MB</span>
                            <input data-detail-file-input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,application/pdf,image/*" class="sr-only">
                        </label>
                        <div data-file-list class="mt-2 flex flex-wrap gap-2"></div>
                    </div>
                `;
                const card = document.createElement('article');
                card.setAttribute('data-detail-row', '');
                card.className = 'procurement-detail-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm';
                card.innerHTML = eight ? `
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span data-row-index class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white">1</span>
                            <div>
                                <div class="text-sm font-semibold text-slate-800">รายการจัดซื้อจัดจ้าง</div>
                                <div class="text-[11px] text-slate-500">กรอกข้อมูลรายการที่ขอซื้อหรือจ้างและแนบเอกสาร</div>
                            </div>
                        </div>
                        <button type="button" data-remove-row class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                            <i class="fas fa-trash-alt"></i> ลบ
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-bold text-slate-700">รายการที่ขอซื้อหรือจ้าง</label>
                            <textarea data-field="item" rows="2" placeholder="ระบุรายการที่ขอซื้อหรือจ้าง" class="${fieldClass} min-h-[4rem] resize-y"></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">จำนวน</label>
                            <input data-field="quantity" type="text" placeholder="เช่น 1 งาน" class="${fieldClass}">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">ผู้ชนะการเสนอราคา</label>
                            <input data-field="winner" type="text" autocomplete="off" placeholder="พิมพ์เพื่อค้นหาหรือระบุชื่อผู้ชนะการเสนอราคา" class="${fieldClass}">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">ราคาที่เสนอ</label>
                            ${moneyInput('proposed_price')}
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">ราคาที่ตกลงซื้อหรือจ้าง</label>
                            ${moneyInput('agreed_price')}
                        </div>
                        ${fileBlock('เอกสารแนบ')}
                    </div>
                ` : `
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span data-row-index class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white">1</span>
                            <div>
                                <div class="text-sm font-semibold text-slate-800">รายการจัดซื้อจัดจ้าง</div>
                                <div class="text-[11px] text-slate-500">กรอกข้อมูลเอกสารและแนบไฟล์ของรายการนี้</div>
                            </div>
                        </div>
                        <button type="button" data-remove-row class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                            <i class="fas fa-trash-alt"></i> ลบ
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-bold text-slate-700">บริษัท / ห้างร้าน</label>
                            <input data-field="company" type="text" placeholder="เช่น บริษัท ตัวอย่าง จำกัด" class="${fieldClass}">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">เลขที่เอกสาร</label>
                            <input data-field="document_type" type="text" placeholder="เช่น ใบเสร็จเลขที่ 123/2569" class="${fieldClass}">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">วันที่เอกสาร</label>
                            <div data-date-wrap class="${groupClass} cursor-pointer">
                                <span class="pl-3 text-slate-400">
                                    <i class="far fa-calendar-alt text-[13px]"></i>
                                </span>
                                <input data-field="document_date" data-pbm-date-picker-input type="text" inputmode="numeric" autocomplete="off" placeholder="เลือกวันที่" class="${innerClass} cursor-pointer px-2.5">
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-bold text-slate-700">ค่าใช้จ่าย</label>
                            <textarea data-field="description" rows="3" placeholder="เช่น ค่าน้ำมัน ค่าจ้างเหมา" class="${fieldClass} min-h-[4.5rem] resize-y"></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">จำนวนเงิน</label>
                            ${moneyInput('amount')}
                        </div>
                        ${fileBlock('เอกสารหลักฐาน')}
                    </div>
                `;
                list.appendChild(card);
                const setField = (name, value) => {
                    const el = card.querySelector(`[data-field="${name}"]`);
                    if (el) el.value = value == null ? '' : String(value);
                };
                const dateInput = card.querySelector('[data-field="document_date"]');
                if (dateInput) dateInput.value = toIsoDate(preset.document_date) || preset.document_date || '';
                setField('company', preset.company || '');
                setField('document_type', preset.document_type || '');
                setField('description', preset.description || '');
                setField('amount', preset.amount ?? '');
                setField('item', preset.item || preset.description || '');
                setField('quantity', preset.quantity || (eight ? '1' : ''));
                setField('winner', preset.winner || preset.company || '');
                setField('proposed_price', preset.proposed_price ?? '');
                setField('agreed_price', preset.agreed_price ?? preset.amount ?? '');
                card._existingFiles = Array.isArray(preset.files) ? preset.files.map((file) => ({
                    id: Number(file?.id || 0),
                    name: String(file?.name || '').trim(),
                    url: String(file?.url || '').trim(),
                })).filter((file) => file.id > 0 || file.name !== '') : [];
                card._newFiles = [];
                renderRowFiles(card);
                window.PBMDatePicker?.initAll?.();
                const fp = dateInput?._flatpickr;
                if (fp?.altInput) {
                    fp.altInput.className = dateInput.className;
                    fp.altInput.style.flex = '1 1 auto';
                    fp.altInput.style.width = 'auto';
                    fp.altInput.style.minWidth = '0';
                    fp.altInput.style.border = '0';
                    fp.altInput.style.background = 'transparent';
                    fp.altInput.style.boxShadow = 'none';
                }
                dateInput?.closest('[data-date-wrap]')?.addEventListener('click', () => {
                    dateInput._flatpickr?.open();
                });
                renumberDetailRows();
                updateDetailEmpty();
                updateDetailTotal();
            };
            const addWinnerRow = (preset = {}) => {
                const tbody = val('procurement-winner-rows');
                if (!tbody) return;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-2 py-1"><input data-field="name" type="text" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></td>
                    <td class="px-2 py-1"><input data-field="amount" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-right text-sm"></td>
                    <td class="px-2 py-1 text-center"><button type="button" data-remove-row class="text-rose-600"><i class="fas fa-times"></i></button></td>
                `;
                tbody.appendChild(tr);
                tr.querySelector('[data-field="name"]').value = preset.name || '';
                tr.querySelector('[data-field="amount"]').value = preset.amount ?? '';
            };
            const addPurchaseRow = (preset = {}) => {
                const tbody = val('procurement-purchase-rows');
                if (!tbody) return;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-2 py-1"><input data-field="name" type="text" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></td>
                    <td class="px-2 py-1"><input data-field="quantity" type="text" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></td>
                    <td class="px-2 py-1 text-center"><button type="button" data-remove-row class="text-rose-600"><i class="fas fa-times"></i></button></td>
                `;
                tbody.appendChild(tr);
                tr.querySelector('[data-field="name"]').value = preset.name || '';
                tr.querySelector('[data-field="quantity"]').value = preset.quantity || '1';
            };
            const addSpecRow = (preset = {}) => {
                const tbody = val('procurement-spec-rows');
                if (!tbody) return;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-2 py-1"><input data-field="name" type="text" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></td>
                    <td class="px-2 py-1"><input data-field="detail" type="text" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></td>
                    <td class="px-2 py-1 text-center"><button type="button" data-remove-row class="text-rose-600"><i class="fas fa-times"></i></button></td>
                `;
                tbody.appendChild(tr);
                tr.querySelector('[data-field="name"]').value = preset.name || '';
                tr.querySelector('[data-field="detail"]').value = preset.detail || '';
            };
            const addPriceRow = (preset = {}) => {
                const wrap = val('procurement-price-rows');
                if (!wrap) return;
                const row = document.createElement('div');
                row.className = 'procurement-price-row flex gap-2';
                row.innerHTML = `
                    <input data-field="name" type="text" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                    <button type="button" data-remove-row class="shrink-0 text-rose-600 px-2"><i class="fas fa-times"></i></button>
                `;
                wrap.appendChild(row);
                row.querySelector('[data-field="name"]').value = preset.name || '';
            };
            const addConsiderRow = (preset = {}) => {
                const tbody = val('procurement-consider-rows');
                if (!tbody) return;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-2 py-1"><input data-field="item" type="text" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></td>
                    <td class="px-2 py-1"><input data-field="winner" type="text" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></td>
                    <td class="px-2 py-1"><input data-field="proposed_price" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-right text-sm"></td>
                    <td class="px-2 py-1"><input data-field="agreed_price" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-right text-sm"></td>
                    <td class="px-2 py-1 text-center"><button type="button" data-remove-row class="text-rose-600"><i class="fas fa-times"></i></button></td>
                `;
                tbody.appendChild(tr);
                tr.querySelector('[data-field="item"]').value = preset.item || '';
                tr.querySelector('[data-field="winner"]').value = preset.winner || '';
                tr.querySelector('[data-field="proposed_price"]').value = preset.proposed_price ?? '';
                tr.querySelector('[data-field="agreed_price"]').value = preset.agreed_price ?? '';
            };

            const destroyDatePickers = (root) => {
                if (!root) return;
                root.querySelectorAll('[data-pbm-date-picker-input]').forEach((input) => {
                    try {
                        input._flatpickr?.destroy();
                    } catch (error) {}
                });
            };

            const resetTables = () => {
                const detailRows = val('procurement-detail-rows');
                destroyDatePickers(detailRows);
                ['procurement-detail-rows', 'procurement-winner-rows', 'procurement-purchase-rows', 'procurement-spec-rows', 'procurement-consider-rows'].forEach((id) => {
                    const el = val(id);
                    if (el) el.innerHTML = '';
                });
                const priceWrap = val('procurement-price-rows');
                if (priceWrap) priceWrap.innerHTML = '';
                updateDetailEmpty();
                updateDetailTotal();
            };

            const applyType = (type, options = {}) => {
                const isEight = type === 'eight';
                const setDefaults = options.setDefaults !== false;
                setVal('procurement-type', isEight ? 'eight' : 'w119');
                const label = val('procurement-type-label');
                if (label) label.textContent = isEight ? 'เอกสารจัดซื้อจัดจ้าง (8 ข้อ)' : 'เอกสารจัดซื้อจัดจ้าง (ว.119)';
                val('procurement-w119-fields')?.classList.remove('hidden');
                val('procurement-eight-extra')?.classList.toggle('hidden', !isEight);
                val('procurement-eight-fields')?.classList.add('hidden');
                val('procurement-header-fields')?.classList.add('hidden');
                val('procurement-signer-fields')?.classList.add('hidden');
                val('procurement-w119-announce')?.classList.add('hidden');
                const hint = val('procurement-form-hint');
                if (hint) {
                    hint.classList.remove('hidden');
                    hint.textContent = isEight
                        ? 'กรอกรายละเอียดประกอบรายงาน และข้อมูล 8 ข้อเพิ่มเติม ข้อมูลอื่นดึงจากรายการเบิกจ่าย และการอนุมัติเป็นไปตามเส้นทางของรายการเบิกจ่าย'
                        : 'กรอกเฉพาะรายละเอียดประกอบรายงาน ข้อมูลอื่นดึงจากรายการเบิกจ่าย และการอนุมัติเป็นไปตามเส้นทางของรายการเบิกจ่าย';
                }
                if (setDefaults) {
                    setVal('procurement-subject', isEight
                        ? 'รายงานขอความเห็นชอบการจัดซื้อหรือจัดจ้างโดยวิธีเฉพาะเจาะจง'
                        : 'รายงานขอความเห็นชอบการจัดซื้อจัดจ้าง');
                    setVal('procurement-finance-position', isEight ? 'หัวหน้าเจ้าหน้าที่' : 'หัวหน้างานการเงิน การคลังและพัสดุ');
                    setVal('procurement-announce-subject', isEight
                        ? 'รายชื่อผู้ชนะการคัดเลือกจัดซื้อ/จัดจ้าง โดยวิธีเฉพาะเจาะจง'
                        : 'รายชื่อผู้ชนะการคัดเลือกจัดซื้อจัดจ้าง โดยวิธีเฉพาะเจาะจง');
                }
                window.PBMSearchableDropdown?.initAll?.();
            };

            const fillFromDetail = (detail) => {
                const allItems = Array.isArray(detail?.items) ? detail.items : [];
                const selectedId = Number(val('procurement-item-id')?.value || 0);
                const selectedItem = selectedId > 0
                    ? allItems.find((item) => Number(item?.id || 0) === selectedId)
                    : null;
                const items = selectedItem ? [selectedItem] : allItems;
                const amount = selectedItem
                    ? Number(selectedItem.amount || 0)
                    : (Number(detail?.total_amount || 0) || items.reduce((sum, item) => sum + Number(item?.amount || 0), 0));
                const dateIso = toIsoDate(detail?.document_date) || todayIso();
                const department = String(detail?.department_display || detail?.department_name || '').trim();
                const procurement = selectedItem
                    ? String(selectedItem.name || '').trim()
                    : String(detail?.project_activity_name || items.map((item) => item?.name).filter(Boolean).join(', ') || '').trim();
                const purpose = String(detail?.project_activity_detail || detail?.activity_detail || '').trim();
                setVal('procurement-department', department);
                setVal('procurement-document-number', String(detail?.document_number_display || detail?.document_number || '').trim());
                setVal('procurement-document-date', dateIso);
                setVal('procurement-item', procurement);
                setVal('procurement-purpose', purpose);
                setVal('procurement-amount', amount || '');
                setVal('procurement-requester-name', String(detail?.applicant_name || '').trim());
                setVal('procurement-requester-position', String(detail?.applicant_position || '').trim());
                setVal('procurement-announce-date', dateIso);
                setVal('procurement-median-price', amount || '');
                setVal('procurement-budget-amount', amount || '');
                const projectAmount = Number(detail?.project_amount || 0);
                setVal('procurement-allocated-budget', projectAmount > 0 ? projectAmount : (amount || ''));
                setVal('procurement-bk-project', String(detail?.project_name || '').trim());
                setVal('procurement-bk-owner', department);
                setVal('procurement-price-date', dateIso);
                setVal('procurement-consider-date', dateIso);
                setVal('procurement-consider-ref-date', dateIso);
                setVal('procurement-consider-number', String(detail?.document_number_display || detail?.document_number || '').trim());
                setVal('procurement-consider-ref-number', String(detail?.document_number_display || detail?.document_number || '').trim());
                setVal('procurement-reason', '');
                setVal('procurement-tor', '');
                setInspectorStaff('', '');
                setVal('procurement-timeline-days', '15');
                setVal('procurement-timeline', 'ภายใน 15 วัน');
                if (!String(val('procurement-budget-source')?.value || '').trim()) {
                    const budgetName = String(
                        selectedItem?.budget_source_name
                        || detail?.budget_name
                        || selectedItem?.budget_manage_code
                        || items[0]?.budget_manage_code
                        || ''
                    ).trim();
                    if (budgetName !== '') {
                        setVal('procurement-budget-source', budgetName);
                    }
                }
                resetTables();
                addDetailRow();
                if (items.length > 0) {
                    items.forEach((item) => {
                        const description = String(item?.name || procurement || '').trim();
                        const itemAmount = Number(item?.amount || 0);
                        addWinnerRow({ name: '', amount: itemAmount });
                        addPurchaseRow({ name: description, quantity: '1' });
                        addSpecRow({ name: description, detail: '' });
                        addPriceRow({ name: description });
                        addConsiderRow({
                            item: description,
                            winner: '',
                            proposed_price: itemAmount,
                            agreed_price: itemAmount,
                        });
                    });
                } else {
                    addWinnerRow({ amount });
                    addPurchaseRow({ name: procurement, quantity: '1' });
                    addSpecRow({ name: procurement });
                    addPriceRow({ name: procurement });
                    addConsiderRow({ item: procurement, proposed_price: amount, agreed_price: amount });
                }
                updateSelectedItemLabels();
                updateDetailTotal();
            };

            const fillRows = (rows, addFn, fallback) => {
                const list = Array.isArray(rows) ? rows : [];
                if (list.length > 0) {
                    list.forEach((row) => addFn(row || {}));
                    return;
                }
                addFn(fallback || {});
            };

            const fillFromPayload = (payload) => {
                const p = payload && typeof payload === 'object' ? payload : {};
                setVal('procurement-item-id', p.withdraw_item_id || '');
                setVal('procurement-department', p.department || '');
                setVal('procurement-phone', p.phone || '');
                setVal('procurement-document-number', p.document_number || '');
                setVal('procurement-document-date', toIsoDate(p.document_date) || p.document_date || '');
                setVal('procurement-subject', p.subject || '');
                setVal('procurement-to', p.to || 'คณบดีคณะเภสัชศาสตร์');
                setVal('procurement-item', p.procurement || '');
                setVal('procurement-purpose', p.purpose || '');
                setVal('procurement-amount', p.amount ?? '');
                setVal('procurement-budget-source', p.budget_source || '');
                setVal('procurement-requester-name', p.requester_name || '');
                setVal('procurement-requester-position', p.requester_position || '');
                setVal('procurement-finance-name', p.finance_name || p.officer_name || '');
                setVal('procurement-finance-position', p.finance_position || p.officer_position || '');
                setVal('procurement-approver-name', p.approver_name || '');
                setVal('procurement-approver-position', p.approver_position || '');
                setVal('procurement-announce-subject', p.announce_subject || '');
                setVal('procurement-announce-date', toIsoDate(p.announce_date) || p.announce_date || '');
                setVal('procurement-reason', p.reason || '');
                setVal('procurement-scope', p.scope || 'เอกสารแนบ');
                setVal('procurement-median-price', p.median_price ?? '');
                setVal('procurement-budget-amount', p.budget_amount ?? '');
                setVal('procurement-timeline-days', p.timeline_days || parseTimelineDays(p.timeline));
                setVal('procurement-timeline', p.timeline || `ภายใน ${parseTimelineDays(p.timeline_days || p.timeline)} วัน`);
                setVal('procurement-method', p.method || '');
                setVal('procurement-criteria', p.criteria || '');
                setVal('procurement-tor', p.tor || '');
                setVal('procurement-inspector', p.inspector || p.spec_signer_name || '');
                setVal('procurement-bk-project', p.bk_project_name || '');
                setVal('procurement-bk-owner', p.bk_owner || '');
                const savedProjectAmount = Number(cachedDetail?.project_amount || 0);
                setVal('procurement-allocated-budget', savedProjectAmount > 0 ? savedProjectAmount : (p.allocated_budget ?? ''));
                setVal('procurement-price-date', toIsoDate(p.price_date) || p.price_date || '');
                setVal('procurement-price-source', p.price_source || 'market');
                setVal('procurement-price-source-detail', p.price_source_detail || '');
                setVal('procurement-price-officer', p.price_officer || p.inspector || '');
                setVal('procurement-consider-number', p.consider_number || '');
                setVal('procurement-consider-date', toIsoDate(p.consider_date) || p.consider_date || '');
                setVal('procurement-consider-ref-number', p.consider_ref_number || '');
                setVal('procurement-consider-ref-date', toIsoDate(p.consider_ref_date) || p.consider_ref_date || '');
                setVal('procurement-winner-name', p.winner_name || '');
                resetTables();
                const savedDetails = Array.isArray(p.detail_rows) ? p.detail_rows : [];
                const eightFallback = isEightForm() && savedDetails.length === 0 && Array.isArray(p.consider_rows) ? p.consider_rows : [];
                fillRows((savedDetails.length > 0 ? savedDetails : eightFallback).map((row) => {
                    const item = row && typeof row === 'object' ? row : {};
                    return {
                        ...item,
                        document_date: toIsoDate(item.document_date) || item.document_date || '',
                    };
                }), addDetailRow);
                fillRows(p.winners, addWinnerRow);
                fillRows(p.purchase_items, addPurchaseRow);
                fillRows(p.spec_items, addSpecRow);
                fillRows(p.price_items, addPriceRow);
                fillRows(p.consider_rows, addConsiderRow);
                updateSelectedItemLabels();
                updateDetailTotal();
                setInspectorStaff(p.inspector_staff_id || '', p.inspector || p.spec_signer_name || '');
            };

            const setInspectorStaff = (staffId, fallbackName = '') => {
                const hidden = val('procurement-inspector-staff');
                const text = val('procurement-inspector-staff-text');
                if (hidden) {
                    hidden.value = String(staffId || '');
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));
                    hidden.dispatchEvent(new Event('change', { bubbles: true }));
                }
                window.PBMSearchableDropdown?.initAll?.();
                if (text && String(staffId || '').trim() === '' && String(fallbackName || '').trim() !== '') {
                    text.value = String(fallbackName);
                }
                const name = String(text?.value || fallbackName || '').trim();
                setVal('procurement-inspector', name);
            };

            const collectPayload = () => {
                const type = String(val('procurement-type')?.value || 'w119');
                const detailRows = collectDetailRows();
                const inspectorName = String(val('procurement-inspector-staff-text')?.value || val('procurement-inspector')?.value || '').trim();
                const inspectorId = String(val('procurement-inspector-staff')?.value || '').trim();
                if (val('procurement-inspector')) val('procurement-inspector').value = inspectorName;
                const shared = {
                    type,
                    withdraw_item_id: Number(val('procurement-item-id')?.value || 0) || null,
                    department: val('procurement-department')?.value || '',
                    phone: val('procurement-phone')?.value || '',
                    document_number: val('procurement-document-number')?.value || '',
                    document_date: val('procurement-document-date')?.value || '',
                    subject: val('procurement-subject')?.value || '',
                    to: val('procurement-to')?.value || '',
                    procurement: val('procurement-item')?.value || '',
                    purpose: val('procurement-purpose')?.value || '',
                    amount: val('procurement-amount')?.value || '',
                    budget_source: val('procurement-budget-source')?.value || '',
                    requester_name: val('procurement-requester-name')?.value || '',
                    requester_position: val('procurement-requester-position')?.value || '',
                    finance_name: val('procurement-finance-name')?.value || '',
                    finance_position: val('procurement-finance-position')?.value || '',
                    officer_name: val('procurement-finance-name')?.value || '',
                    officer_position: val('procurement-finance-position')?.value || '',
                    approver_name: val('procurement-approver-name')?.value || '',
                    approver_position: val('procurement-approver-position')?.value || '',
                    announce_subject: val('procurement-announce-subject')?.value || '',
                    announce_date: val('procurement-announce-date')?.value || val('procurement-document-date')?.value || '',
                    attachment_count: String(Math.max(detailRows.length, 1)),
                    detail_rows: detailRows,
                    winners: detailRows.map((row) => ({
                        name: row.company || '',
                        amount: row.amount || '',
                    })),
                };
                if (type === 'eight') {
                    const torRaw = String(val('procurement-tor')?.value ?? '').replace(/\r\n/g, '\n');
                    const tor = torRaw.trim() === '' ? '' : torRaw;
                    const days = parseTimelineDays(val('procurement-timeline-days')?.value || '15');
                    const timeline = `ภายใน ${days} วัน`;
                    setVal('procurement-timeline', timeline);
                    const filledDetails = detailRows.filter((row) => row.item || row.winner || row.quantity || row.proposed_price || row.agreed_price);
                    return {
                        ...shared,
                        reason: val('procurement-reason')?.value || '',
                        inspector: inspectorName,
                        inspector_staff_id: inspectorId,
                        spec_signer_name: inspectorName,
                        tor,
                        scope: 'เอกสารแนบ',
                        median_price: val('procurement-median-price')?.value || shared.amount,
                        budget_amount: val('procurement-budget-amount')?.value || shared.amount,
                        timeline_days: days,
                        timeline,
                        method: val('procurement-method')?.value || '',
                        criteria: val('procurement-criteria')?.value || '',
                        purchase_items: filledDetails.length > 0
                            ? filledDetails.map((row) => ({
                                name: row.item || '',
                                quantity: row.quantity || '1',
                            }))
                            : collectRows('procurement-purchase-rows', ['name', 'quantity']),
                        spec_items: tor !== ''
                            ? [{ name: 'รายละเอียดคุณลักษณะเฉพาะของพัสดุ', detail: tor }]
                            : collectRows('procurement-spec-rows', ['name', 'detail']),
                        bk_project_name: val('procurement-bk-project')?.value || '',
                        bk_owner: val('procurement-bk-owner')?.value || '',
                        allocated_budget: val('procurement-allocated-budget')?.value || shared.amount,
                        price_date: val('procurement-price-date')?.value || '',
                        price_source: val('procurement-price-source')?.value || 'market',
                        price_source_detail: val('procurement-price-source-detail')?.value || '',
                        price_officer: val('procurement-price-officer')?.value || inspectorName,
                        price_items: Array.from(document.querySelectorAll('#procurement-price-rows [data-field="name"]')).map((input) => ({
                            name: String(input.value || '').trim(),
                        })),
                        consider_number: val('procurement-consider-number')?.value || '',
                        consider_date: val('procurement-consider-date')?.value || '',
                        consider_ref_number: val('procurement-consider-ref-number')?.value || '',
                        consider_ref_date: val('procurement-consider-ref-date')?.value || '',
                        winner_name: val('procurement-winner-name')?.value || filledDetails[0]?.winner || '',
                        consider_rows: filledDetails.length > 0
                            ? filledDetails.map((row) => ({
                                item: row.item || '',
                                winner: row.winner || '',
                                proposed_price: row.proposed_price || '',
                                agreed_price: row.agreed_price || '',
                            }))
                            : collectRows('procurement-consider-rows', ['item', 'winner', 'proposed_price', 'agreed_price']),
                    };
                }
                return shared;
            };

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            const formatAmount = (value) => {
                const num = Number(value);
                if (!Number.isFinite(num)) return '0.00';
                return num.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };

            const selectedItemFromCache = () => {
                const selectedId = Number(val('procurement-item-id')?.value || 0);
                const items = Array.isArray(cachedDetail?.items) ? cachedDetail.items : [];
                if (selectedId > 0) {
                    return items.find((item) => Number(item?.id || 0) === selectedId) || null;
                }
                return null;
            };

            const selectedItemLabel = (item) => {
                if (!item) return '';
                const name = String(item.name || '').trim();
                const amount = formatAmount(item.amount);
                return name !== '' ? `${name} (${amount} บาท)` : `${amount} บาท`;
            };

            const updateSelectedItemLabels = () => {
                const item = selectedItemFromCache();
                const fallback = String(val('procurement-item')?.value || '').trim();
                const label = item ? selectedItemLabel(item) : fallback;
                const typeWrap = val('procurement-type-selected-item');
                const typeLabel = val('procurement-type-selected-item-label');
                const formWrap = val('procurement-form-selected-item');
                const formLabel = val('procurement-form-selected-item-label');
                if (typeLabel) typeLabel.textContent = label || 'ยังไม่ได้เลือกรายการ';
                if (typeWrap) typeWrap.classList.toggle('hidden', !label);
                if (formLabel) formLabel.textContent = label;
                if (formWrap) formWrap.classList.toggle('hidden', !label);
                updateDetailTotal();
            };

            const itemAmountLimit = () => {
                const item = selectedItemFromCache();
                const fromItem = Number(item?.amount || 0);
                if (Number.isFinite(fromItem) && fromItem > 0) return fromItem;
                const fromCache = Number(cachedDetail?.total_amount || 0);
                if (Number.isFinite(fromCache) && fromCache > 0) return fromCache;
                const fromField = Number(val('procurement-amount')?.value || 0);
                return Number.isFinite(fromField) ? fromField : 0;
            };

            const requiresEightType = () => itemAmountLimit() > 10000;

            const updateTypePickerOptions = () => {
                const eightOnly = requiresEightType();
                const w119Btn = document.querySelector('[data-procurement-type-option="w119"]');
                const hint = val('procurement-type-eight-only-hint');
                if (w119Btn) {
                    w119Btn.classList.toggle('hidden', eightOnly);
                    w119Btn.disabled = eightOnly;
                }
                if (hint) hint.classList.toggle('hidden', !eightOnly);
            };

            const detailRowsTotal = () => Array.from(document.querySelectorAll('#procurement-detail-rows [data-field="agreed_price"], #procurement-detail-rows [data-field="amount"]'))
                .reduce((sum, input) => {
                    const num = Number(String(input.value || '').replace(/,/g, '').trim());
                    return sum + (Number.isFinite(num) ? num : 0);
                }, 0);

            const updateDetailTotal = () => {
                const total = detailRowsTotal();
                const limit = itemAmountLimit();
                const over = limit > 0 && (total - limit > 0.009);
                const remaining = limit - total;
                const totalEl = val('procurement-detail-total');
                const limitEl = val('procurement-detail-limit');
                const overEl = val('procurement-detail-over-limit');
                const wrap = val('procurement-detail-total-wrap');
                const remainingEl = val('procurement-detail-remaining');
                const progressEl = val('procurement-detail-progress');
                if (totalEl) totalEl.textContent = formatAmount(total);
                if (limitEl) limitEl.textContent = formatAmount(limit);
                if (overEl) overEl.classList.toggle('hidden', !over);
                if (remainingEl) {
                    remainingEl.textContent = over
                        ? `เกิน ${formatAmount(total - limit)} บาท`
                        : `${formatAmount(Math.max(0, remaining))} บาท`;
                    remainingEl.classList.toggle('text-rose-600', over);
                    remainingEl.classList.toggle('text-emerald-700', !over);
                }
                if (progressEl) {
                    const pct = limit > 0 ? Math.min(100, (total / Math.max(limit, 0.01)) * 100) : 0;
                    progressEl.style.width = `${over ? 100 : pct}%`;
                    progressEl.classList.toggle('bg-rose-500', over);
                    progressEl.classList.toggle('bg-sky-500', !over);
                }
                if (wrap) {
                    wrap.classList.toggle('bg-rose-50', over);
                    wrap.classList.toggle('bg-slate-50', !over);
                }
                return { total, limit, over };
            };

            const queryDetailRows = () => Array.from(document.querySelectorAll('#procurement-detail-rows [data-detail-row]'));

            const detailRowOf = (el) => el?.closest?.('[data-detail-row]') || null;

            const renumberDetailRows = () => {
                queryDetailRows().forEach((card, index) => {
                    const badge = card.querySelector('[data-row-index]');
                    if (badge) badge.textContent = String(index + 1);
                });
            };

            const updateDetailEmpty = () => {
                const empty = val('procurement-detail-empty');
                if (empty) empty.classList.toggle('hidden', queryDetailRows().length > 0);
            };

            let winnerNamesCache = null;
            let winnerSuggestEl = null;
            let winnerActiveInput = null;

            const ensureWinnerSuggest = () => {
                if (winnerSuggestEl) return winnerSuggestEl;
                winnerSuggestEl = document.createElement('div');
                winnerSuggestEl.id = 'procurement-winner-suggest';
                winnerSuggestEl.className = 'fixed z-[100000] hidden max-h-60 overflow-auto rounded-2xl border border-slate-200 bg-white py-1 shadow-2xl';
                document.body.appendChild(winnerSuggestEl);
                return winnerSuggestEl;
            };

            const hideWinnerSuggest = () => {
                ensureWinnerSuggest().classList.add('hidden');
                winnerActiveInput = null;
            };

            const loadWinnerNames = async () => {
                if (Array.isArray(winnerNamesCache)) return winnerNamesCache;
                try {
                    const response = await fetch(winnersApi, { headers: { Accept: 'application/json' } });
                    const result = await response.json();
                    winnerNamesCache = Array.isArray(result?.data) ? result.data : [];
                } catch (error) {
                    winnerNamesCache = [];
                }
                return winnerNamesCache;
            };

            const liveWinnerNames = (exceptInput = null) => {
                const names = [];
                const seen = {};
                queryDetailRows().forEach((row) => {
                    const input = row.querySelector('[data-field="winner"]');
                    const name = String(input?.value || '').trim();
                    if (!name || input === exceptInput) return;
                    const key = name.toLowerCase();
                    if (seen[key]) return;
                    seen[key] = true;
                    names.push(name);
                });
                return names;
            };

            const showWinnerSuggest = async (input) => {
                if (!input) return;
                winnerActiveInput = input;
                const saved = await loadWinnerNames();
                if (winnerActiveInput !== input) return;
                const term = String(input.value || '').trim().toLowerCase();
                const merged = [];
                const seen = {};
                [...liveWinnerNames(input), ...saved].forEach((name) => {
                    const key = String(name || '').trim().toLowerCase();
                    if (!key || seen[key]) return;
                    seen[key] = true;
                    merged.push(String(name).trim());
                });
                const matches = merged.filter((name) => !term || name.toLowerCase().includes(term)).slice(0, 30);
                const el = ensureWinnerSuggest();
                if (matches.length === 0) {
                    el.classList.add('hidden');
                    return;
                }
                el.innerHTML = matches.map((name) => (
                    `<button type="button" data-winner-pick="${encodeURIComponent(name)}" class="w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-sky-50">${escapeHtml(name)}</button>`
                )).join('');
                const box = input.getBoundingClientRect();
                el.style.left = `${box.left}px`;
                el.style.top = `${box.bottom + 4}px`;
                el.style.width = `${Math.max(box.width, 240)}px`;
                el.classList.remove('hidden');
            };

            const collectDetailRows = () => queryDetailRows().map((row) => {
                const item = {};
                ['company', 'document_type', 'document_date', 'description', 'amount', 'item', 'quantity', 'winner', 'proposed_price', 'agreed_price'].forEach((field) => {
                    const el = row.querySelector(`[data-field="${field}"]`);
                    if (!el) return;
                    item[field] = String(el.value || '').trim();
                });
                if (!item.amount && item.agreed_price) item.amount = item.agreed_price;
                item.files = Array.isArray(row._existingFiles)
                    ? row._existingFiles.map((file) => ({
                        id: Number(file?.id || 0),
                        name: String(file?.name || '').trim(),
                    })).filter((file) => file.id > 0)
                    : [];
                return item;
            });

            const renderRowFiles = (tr) => {
                const list = tr?.querySelector('[data-file-list]');
                if (!list) return;
                const existing = Array.isArray(tr._existingFiles) ? tr._existingFiles : [];
                const fresh = Array.isArray(tr._newFiles) ? tr._newFiles : [];
                const parts = [];
                existing.forEach((file) => {
                    const name = escapeHtml(file.name || 'ไฟล์');
                    const href = file.url || (Number(file.id || 0) > 0 ? `${fileApiBase}/${Number(file.id)}` : '');
                    const label = href
                        ? `<a href="${escapeHtml(href)}" target="_blank" rel="noopener noreferrer" class="max-w-[14rem] truncate text-sky-700 hover:underline">${name}</a>`
                        : `<span class="max-w-[14rem] truncate">${name}</span>`;
                    parts.push(`
                        <div class="inline-flex max-w-full items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 shadow-sm">
                            <i class="fas fa-file-alt shrink-0 text-sky-500"></i>
                            ${label}
                            <button type="button" data-remove-existing-file="${Number(file.id || 0)}" class="shrink-0 rounded p-0.5 text-rose-500 hover:bg-rose-50 hover:text-rose-700" title="ลบไฟล์">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `);
                });
                fresh.forEach((file, index) => {
                    parts.push(`
                        <div class="inline-flex max-w-full items-center gap-1.5 rounded-lg border border-sky-100 bg-sky-50 px-2 py-1 text-xs text-slate-600">
                            <i class="fas fa-file-alt shrink-0 text-sky-500"></i>
                            <span class="max-w-[14rem] truncate">${escapeHtml(file.name || 'ไฟล์ใหม่')}</span>
                            <span class="shrink-0 rounded bg-sky-100 px-1 text-[10px] font-semibold text-sky-700">ใหม่</span>
                            <button type="button" data-remove-new-file="${index}" class="shrink-0 rounded p-0.5 text-rose-500 hover:bg-rose-50 hover:text-rose-700" title="ลบไฟล์">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `);
                });
                list.innerHTML = parts.length > 0 ? parts.join('') : '';
            };

            const addFilesToRow = (card, fileList) => {
                if (!card) return;
                if (!Array.isArray(card._newFiles)) card._newFiles = [];
                const allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif'];
                Array.from(fileList || []).forEach((file) => {
                    const ext = String(file.name || '').split('.').pop().toLowerCase();
                    if (!allowed.includes(ext)) {
                        window.toast?.error?.(`ไฟล์ ${file.name} ไม่ใช่ PDF หรือรูปภาพ`, { timer: 2200 });
                        return;
                    }
                    if (file.size > 10 * 1024 * 1024) {
                        window.toast?.error?.(`ไฟล์ ${file.name} มีขนาดเกิน 10 MB`, { timer: 2200 });
                        return;
                    }
                    card._newFiles.push(file);
                });
                renderRowFiles(card);
            };

            const loadDetailCache = async (withdrawId) => {
                const id = Number(withdrawId || 0);
                if (id <= 0) return null;
                if (cachedDetail && cachedDetailId === id) return cachedDetail;
                const response = await fetch(`${detailApiBase}/${id}`, { headers: { Accept: 'application/json' } });
                const result = await response.json();
                if (result?.status === 'success' && result?.data) {
                    cachedDetail = result.data;
                    cachedDetailId = id;
                    return cachedDetail;
                }
                return null;
            };

            const fetchList = async (withdrawId) => {
                const response = await fetch(`${listApi}?withdraw_id=${encodeURIComponent(withdrawId)}`, {
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (!response.ok || result?.status !== 'success') {
                    throw new Error(result?.message || 'list_failed');
                }
                const metaCanEdit = result?.meta?.can_edit;
                if (typeof metaCanEdit === 'boolean') {
                    setProcurementEditMode(metaCanEdit);
                } else if (result?.meta?.withdraw_status != null) {
                    setProcurementEditMode(isEditableWithdrawStatus(result.meta.withdraw_status));
                }
                return Array.isArray(result.data) ? result.data : [];
            };

            const renderList = (rows) => {
                const empty = val('procurement-list-empty');
                const wrap = val('procurement-list-wrap');
                const body = val('procurement-list-body');
                const countEl = val('procurement-list-count');
                const summary = val('procurement-list-summary');
                const hint = val('procurement-list-hint');
                if (!body) return;
                body.innerHTML = '';
                const items = Array.isArray(rows) ? rows : [];
                if (countEl) countEl.textContent = String(items.length);
                if (empty) empty.classList.toggle('hidden', items.length > 0);
                if (wrap) wrap.classList.toggle('hidden', items.length === 0);
                if (summary) summary.classList.toggle('hidden', items.length === 0);
                if (hint) {
                    hint.textContent = items.length > 0
                        ? `มี ${items.length} เอกสาร · เปิด PDF เพื่อตรวจสอบ หรือแก้ไขเมื่อยังอนุญาต`
                        : (canEditProcurement
                            ? 'ยังไม่มีเอกสาร · กดเพิ่มเอกสารเพื่อเริ่มสร้าง'
                            : 'ยังไม่มีเอกสารของรายการนี้');
                }
                setProcurementEditMode(canEditProcurement);
                items.forEach((item, index) => {
                    const card = document.createElement('div');
                    card.className = 'rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm transition hover:border-orange-200 hover:shadow-md';
                    const title = String(item.item_name || item.procurement || item.subject || '-').trim() || '-';
                    const typeCode = String(item.type || '').trim().toLowerCase();
                    const isEight = typeCode === 'eight';
                    const typeShort = isEight ? '8 ข้อ' : 'ว.119';
                    const typeLabel = String(item.type_label || (isEight ? 'เอกสารจัดซื้อจัดจ้าง (8 ข้อ)' : 'เอกสารจัดซื้อจัดจ้าง (ว.119)')).trim();
                    const docNo = String(item.document_number_display || item.document_number || '').trim();
                    const dateText = String(item.date_modify_display || '-').trim() || '-';
                    const rowCanEdit = typeof item.can_edit === 'boolean' ? item.can_edit : canEditProcurement;
                    const typeBadgeClass = isEight
                        ? 'bg-amber-50 text-amber-800 ring-amber-200'
                        : 'bg-sky-50 text-sky-800 ring-sky-200';
                    const editButton = rowCanEdit
                        ? `<button type="button" data-procurement-edit="${Number(item.id || 0)}"
                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 transition hover:bg-sky-100">
                                <i class="fas fa-pen text-[10px]"></i>
                                แก้ไข
                            </button>`
                        : `<span class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-2 text-[11px] font-medium text-slate-400">
                                <i class="fas fa-lock text-[9px]"></i>
                                ดูอย่างเดียว
                            </span>`;
                    card.innerHTML = `
                        <div class="flex w-full items-center justify-between gap-3">
                            <div class="flex min-w-0 flex-1 items-start gap-3 text-left">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                                    <i class="fas fa-file-invoice"></i>
                                </span>
                                <div class="min-w-0 flex-1 text-left">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 ${typeBadgeClass}">${escapeHtml(typeShort)}</span>
                                        <span class="text-[11px] font-medium text-slate-400">#${index + 1}</span>
                                    </div>
                                    <div class="mt-1.5 text-sm font-semibold leading-snug text-slate-800 break-words">${escapeHtml(title)}</div>
                                    <div class="mt-1 text-xs text-slate-500">${escapeHtml(typeLabel)}</div>
                                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-600">
                                        ${docNo !== '' ? `<span class="inline-flex items-center gap-1"><i class="fas fa-hashtag text-[9px] text-slate-400"></i><span class="font-semibold text-slate-700">${escapeHtml(docNo)}</span></span>` : ''}
                                        <span class="inline-flex items-center gap-1"><i class="far fa-clock text-[9px] text-slate-400"></i>${escapeHtml(dateText)}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="ml-auto flex shrink-0 flex-nowrap items-center justify-end gap-1.5">
                                <button type="button" data-procurement-pdf="${Number(item.id || 0)}"
                                    class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-700 transition hover:bg-orange-100">
                                    <i class="fas fa-file-pdf text-[10px]"></i>
                                    เปิด PDF
                                </button>
                                ${editButton}
                            </div>
                        </div>
                    `;
                    body.appendChild(card);
                });
            };

            const renderItemPicker = (detail) => {
                const list = val('procurement-item-picker-list');
                const empty = val('procurement-item-picker-empty');
                const skip = val('procurement-item-picker-skip');
                if (!list) return [];
                list.innerHTML = '';
                const items = Array.isArray(detail?.items) ? detail.items : [];
                if (empty) empty.classList.toggle('hidden', items.length > 0);
                if (skip) skip.classList.toggle('hidden', items.length > 0);
                items.forEach((item, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.setAttribute('data-procurement-pick-item', String(item.id || 0));
                    button.className = 'w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left transition hover:border-sky-300 hover:bg-sky-50/60';
                    const name = String(item.name || '').trim() || `รายการที่ ${index + 1}`;
                    const category = String(item.category || item.expense_chart_name || '').trim();
                    const budget = String(item.budget_manage_code || '').trim();
                    const meta = [budget ? `งบประมาณ ${budget}` : '', category ? `หมวด ${category}` : ''].filter(Boolean).join(' · ');
                    button.innerHTML = `
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-800">${escapeHtml(name)}</div>
                                ${meta ? `<div class="mt-0.5 text-xs text-slate-500">${escapeHtml(meta)}</div>` : ''}
                            </div>
                            <div class="shrink-0 text-sm font-semibold text-sky-700">${escapeHtml(formatAmount(item.amount))} บาท</div>
                        </div>
                    `;
                    list.appendChild(button);
                });
                return items;
            };

            const openListModal = async (withdrawId) => {
                const id = Number(withdrawId || val('procurement-withdraw-id')?.value || 0);
                if (id <= 0) return [];
                setVal('procurement-withdraw-id', id);
                const rows = await fetchList(id);
                renderList(rows);
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-procurement-list-modal' }));
                return rows;
            };

            const openTypePicker = (id) => {
                if (!canEditProcurement) {
                    window.toast?.error?.(lockedEditMessage, { timer: 2800 });
                    return;
                }
                if (id != null && id !== '') {
                    setVal('procurement-withdraw-id', Number(id || 0) || '');
                }
                setVal('procurement-id', '');
                updateSelectedItemLabels();
                updateTypePickerOptions();
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-procurement-type-modal' }));
            };

            const openItemPicker = async (id) => {
                if (!canEditProcurement) {
                    window.toast?.error?.(lockedEditMessage, { timer: 2800 });
                    return;
                }
                const withdrawId = Number(id || val('procurement-withdraw-id')?.value || 0);
                if (withdrawId > 0) {
                    setVal('procurement-withdraw-id', withdrawId);
                }
                setVal('procurement-id', '');
                try {
                    const detail = await loadDetailCache(withdrawId);
                    const items = renderItemPicker(detail);
                    if (items.length === 0) {
                        openTypePicker(withdrawId);
                        return;
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-procurement-item-modal' }));
                } catch (error) {
                    window.toast?.error?.('ไม่สามารถโหลดรายการเบิกจ่ายได้', { timer: 2200 });
                    openTypePicker(withdrawId);
                }
            };

            const selectWithdrawItem = (itemId) => {
                setVal('procurement-item-id', Number(itemId || 0) || '');
                updateSelectedItemLabels();
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-procurement-item-modal' }));
                openTypePicker();
            };

            const openForm = (type, saved = null, recordId = 0) => {
                setVal('procurement-id', Number(recordId || 0) || '');
                if (!saved && requiresEightType()) {
                    type = 'eight';
                }
                applyType(type, { setDefaults: !saved });
                if (saved && typeof saved === 'object') {
                    fillFromPayload(saved);
                    return;
                }
                const withdrawId = Number(val('procurement-withdraw-id')?.value || 0);
                if (cachedDetail && cachedDetailId === withdrawId) {
                    fillFromDetail(cachedDetail);
                    return;
                }
                const loadDetail = async () => {
                    try {
                        const detail = await loadDetailCache(withdrawId);
                        fillFromDetail(detail || {});
                    } catch (error) {
                        fillFromDetail({});
                    }
                };
                loadDetail();
            };

            const openForWithdraw = async (id, options = {}) => {
                const withdrawId = Number(id || 0);
                if (withdrawId <= 0) return;
                setVal('procurement-withdraw-id', withdrawId);
                setVal('procurement-id', '');
                setVal('procurement-item-id', '');
                cachedDetail = null;
                cachedDetailId = 0;
                if (options && typeof options === 'object') {
                    if (typeof options.canEdit === 'boolean') {
                        setProcurementEditMode(options.canEdit);
                    } else if (options.status != null) {
                        setProcurementEditMode(isEditableWithdrawStatus(options.status));
                    }
                }
                try {
                    const [rows] = await Promise.all([
                        fetchList(withdrawId),
                        loadDetailCache(withdrawId).catch(() => null),
                    ]);
                    if (rows.length === 0) {
                        if (!canEditProcurement) {
                            renderList([]);
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-procurement-list-modal' }));
                            window.toast?.info?.(lockedEditMessage, { timer: 2800 });
                            return;
                        }
                        await openItemPicker(withdrawId);
                        return;
                    }
                    renderList(rows);
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-procurement-list-modal' }));
                } catch (error) {
                    window.toast?.error?.('ไม่สามารถโหลดรายการเอกสารได้', { timer: 2200 });
                    if (canEditProcurement) {
                        await openItemPicker(withdrawId);
                    }
                }
            };

            const editRecord = async (recordId) => {
                const id = Number(recordId || 0);
                if (id <= 0) return;
                if (!canEditProcurement) {
                    window.toast?.error?.(lockedEditMessage, { timer: 2800 });
                    return;
                }
                try {
                    const response = await fetch(`${listApi}/${id}`, { headers: { Accept: 'application/json' } });
                    const result = await response.json();
                    if (!response.ok || result?.status !== 'success' || !result?.data) {
                        throw new Error(result?.message || 'show_failed');
                    }
                    const payload = result.data.payload || {};
                    if (result.data.withdraw_item_id) {
                        payload.withdraw_item_id = result.data.withdraw_item_id;
                    }
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-procurement-list-modal' }));
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'withdraw-procurement-form-modal' }));
                    openForm(result.data.type || 'w119', payload, result.data.id);
                } catch (error) {
                    window.toast?.error?.('ไม่สามารถเปิดเอกสารเพื่อแก้ไขได้', { timer: 2200 });
                }
            };

            window.WithdrawProjectProcurement = {
                openTypePicker,
                openItemPicker,
                openForWithdraw,
                openForm,
            };

            document.addEventListener('click', (event) => {
                const pickWinner = event.target.closest('[data-winner-pick]');
                if (pickWinner) {
                    event.preventDefault();
                    event.stopPropagation();
                    const name = decodeURIComponent(pickWinner.getAttribute('data-winner-pick') || '');
                    if (winnerActiveInput) {
                        winnerActiveInput.value = name;
                        winnerActiveInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    hideWinnerSuggest();
                    return;
                }
                if (!event.target.closest('#procurement-winner-suggest') && !event.target.closest('#procurement-detail-rows [data-field="winner"]')) {
                    hideWinnerSuggest();
                }
                if (event.target.closest('#procurement-list-add') || event.target.closest('#procurement-list-empty-add')) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (!canEditProcurement) {
                        window.toast?.error?.(lockedEditMessage, { timer: 2800 });
                        return;
                    }
                    setVal('procurement-id', '');
                    setVal('procurement-item-id', '');
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-procurement-list-modal' }));
                    openItemPicker();
                    return;
                }
                if (event.target.closest('#procurement-item-picker-skip')) {
                    event.preventDefault();
                    event.stopPropagation();
                    setVal('procurement-item-id', '');
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-procurement-item-modal' }));
                    openTypePicker();
                    return;
                }
                if (event.target.closest('#procurement-type-change-item')) {
                    event.preventDefault();
                    event.stopPropagation();
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-procurement-type-modal' }));
                    openItemPicker();
                    return;
                }
                const pickItem = event.target.closest('[data-procurement-pick-item]');
                if (pickItem) {
                    event.preventDefault();
                    event.stopPropagation();
                    selectWithdrawItem(pickItem.getAttribute('data-procurement-pick-item'));
                    return;
                }
                const editButton = event.target.closest('[data-procurement-edit]');
                if (editButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    editRecord(editButton.getAttribute('data-procurement-edit'));
                    return;
                }
                const pdfButton = event.target.closest('[data-procurement-pdf]');
                if (pdfButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    const id = Number(pdfButton.getAttribute('data-procurement-pdf') || 0);
                    if (id > 0) {
                        window.open(`${pdfByIdBase}/${id}/pdf`, '_blank', 'noopener,noreferrer');
                    }
                    return;
                }
                if (event.target.closest('[data-add-detail-row]')) {
                    event.preventDefault();
                    addDetailRow();
                    const cards = queryDetailRows();
                    cards[cards.length - 1]?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    cards[cards.length - 1]?.querySelector('[data-field="company"]')?.focus();
                    return;
                }
                const removeExistingFile = event.target.closest('#withdraw-procurement-form [data-remove-existing-file]');
                if (removeExistingFile) {
                    event.preventDefault();
                    event.stopPropagation();
                    const card = detailRowOf(removeExistingFile);
                    const fileId = Number(removeExistingFile.getAttribute('data-remove-existing-file') || 0);
                    if (card) {
                        card._existingFiles = (Array.isArray(card._existingFiles) ? card._existingFiles : [])
                            .filter((file) => Number(file?.id || 0) !== fileId);
                        renderRowFiles(card);
                    }
                    return;
                }
                const removeNewFile = event.target.closest('#withdraw-procurement-form [data-remove-new-file]');
                if (removeNewFile) {
                    event.preventDefault();
                    event.stopPropagation();
                    const card = detailRowOf(removeNewFile);
                    const index = Number(removeNewFile.getAttribute('data-remove-new-file') || -1);
                    if (card && index >= 0 && Array.isArray(card._newFiles)) {
                        card._newFiles.splice(index, 1);
                        renderRowFiles(card);
                    }
                    return;
                }
                if (event.target.closest('#procurement-add-winner-row')) {
                    addWinnerRow();
                    return;
                }
                if (event.target.closest('#procurement-add-purchase-row')) {
                    addPurchaseRow();
                    return;
                }
                if (event.target.closest('#procurement-add-spec-row')) {
                    addSpecRow();
                    return;
                }
                if (event.target.closest('#procurement-add-price-row')) {
                    addPriceRow();
                    return;
                }
                if (event.target.closest('#procurement-add-consider-row')) {
                    addConsiderRow();
                    return;
                }
                const removeButton = event.target.closest('#withdraw-procurement-form [data-remove-row]');
                if (removeButton) {
                    const row = removeButton.closest('[data-detail-row], tr, .procurement-price-row');
                    destroyDatePickers(row);
                    row?.remove();
                    renumberDetailRows();
                    updateDetailEmpty();
                    updateDetailTotal();
                }
            }, true);

            document.addEventListener('input', (event) => {
                if (event.target?.closest('#procurement-detail-rows [data-field="amount"], #procurement-detail-rows [data-field="agreed_price"]')) {
                    updateDetailTotal();
                }
                if (event.target?.matches?.('#procurement-detail-rows [data-field="winner"]')) {
                    showWinnerSuggest(event.target);
                }
            });

            document.addEventListener('focusin', (event) => {
                if (event.target?.matches?.('#procurement-detail-rows [data-field="winner"]')) {
                    showWinnerSuggest(event.target);
                }
            });

            document.addEventListener('scroll', (event) => {
                if (winnerActiveInput && event.target !== winnerSuggestEl) {
                    hideWinnerSuggest();
                }
            }, true);

            document.addEventListener('change', (event) => {
                const input = event.target?.closest?.('#withdraw-procurement-form [data-detail-file-input]');
                if (!input) return;
                const card = detailRowOf(input);
                addFilesToRow(card, input.files);
                input.value = '';
            });

            const detailRowsWrap = val('procurement-detail-rows');
            if (detailRowsWrap) {
                const dropZoneOf = (target) => target?.closest?.('[data-file-drop]') || null;
                detailRowsWrap.addEventListener('dragover', (event) => {
                    const zone = dropZoneOf(event.target);
                    if (!zone) return;
                    event.preventDefault();
                    zone.classList.add('border-sky-400', 'bg-sky-50');
                });
                detailRowsWrap.addEventListener('dragleave', (event) => {
                    const zone = dropZoneOf(event.target);
                    if (!zone) return;
                    if (event.relatedTarget && zone.contains(event.relatedTarget)) return;
                    zone.classList.remove('border-sky-400', 'bg-sky-50');
                });
                detailRowsWrap.addEventListener('drop', (event) => {
                    const zone = dropZoneOf(event.target);
                    if (!zone) return;
                    event.preventDefault();
                    zone.classList.remove('border-sky-400', 'bg-sky-50');
                    addFilesToRow(detailRowOf(zone), event.dataTransfer?.files);
                });
            }

            document.addEventListener('submit', async (event) => {
                if (event.target?.id !== 'withdraw-procurement-form') return;
                event.preventDefault();
                const button = val('procurement-save-button');
                const withdrawId = Number(val('procurement-withdraw-id')?.value || 0);
                if (withdrawId <= 0) {
                    window.toast?.error?.('ไม่พบรายการเบิกจ่ายที่จะบันทึก', { timer: 2200 });
                    return;
                }
                const check = updateDetailTotal();
                if (check.over) {
                    val('procurement-detail-over-limit')?.scrollIntoView?.({ behavior: 'smooth', block: 'center' });
                    window.toast?.error?.(
                        `จำนวนเงินรวม (${formatAmount(check.total)} บาท) เกินวงเงินของรายการเบิกจ่าย (${formatAmount(check.limit)} บาท)`,
                        { timer: 2800 }
                    );
                    return;
                }
                if (button) button.disabled = true;
                try {
                    const recordId = Number(val('procurement-id')?.value || 0);
                    const payload = {
                        ...collectPayload(),
                        id: recordId > 0 ? recordId : 0,
                        withdraw_id: withdrawId,
                    };
                    const formData = new FormData();
                    formData.append('_token', csrfToken);
                    formData.append('payload', JSON.stringify(payload));
                    Array.from(document.querySelectorAll('#procurement-detail-rows [data-detail-row]')).forEach((row, index) => {
                        (Array.isArray(row._newFiles) ? row._newFiles : []).forEach((file) => {
                            formData.append(`detail_files[${index}][]`, file);
                        });
                    });
                    const response = await fetch(saveApi, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });
                    const result = await response.json().catch(() => ({}));
                    if (!response.ok || result?.status !== 'success') {
                        throw new Error(result?.message || 'save_failed');
                    }
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'withdraw-procurement-form-modal' }));
                    window.toast?.success?.(result?.message || 'บันทึกเอกสารจัดซื้อจัดจ้างแล้ว', { timer: 1600 });
                    winnerNamesCache = null;
                    await openListModal(withdrawId);
                } catch (error) {
                    window.toast?.error?.(error?.message && error.message !== 'save_failed'
                        ? error.message
                        : 'ไม่สามารถบันทึกเอกสารได้', { timer: 2200 });
                } finally {
                    if (button) button.disabled = false;
                }
            });
        })();
    </script>
    <?php $__env->stopPush(); ?>
<?php endif; ?>

<?php /**PATH /var/www/application/withdraw/resources/views/withdraw/project/partials/procurement-modals.blade.php ENDPATH**/ ?>