@php
    $canAccess = function (string $menuKey): bool {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'canAccessMenu')) {
            return true;
        }

        return $user->canAccessMenu($menuKey);
    };
    $settingsSectionOpen = request()->routeIs('setting.*', 'setting.memo-templates');
    $expenseChartMenuKey = 'withdraw-settings-expenses';
    $expenseChartActive = request()->routeIs('setting.index')
        && in_array((string) request()->route('module'), ['category-pay', 'pay-list', 'expenses'], true);
    $documentSettingActive = request()->routeIs('setting.index')
        && (string) request()->route('module') === 'documents';
    $bookNumberSettingActive = request()->routeIs('setting.index')
        && (string) request()->route('module') === 'book-numbers';
    $approverSettingActive = request()->routeIs('setting.index')
        && (string) request()->route('module') === 'approvers';
    $financeReviewerSettingActive = request()->routeIs('setting.index')
        && (string) request()->route('module') === 'finance-reviewers';
    $memoTemplateSettingActive = request()->routeIs('setting.memo-templates');
    $approvalListActive = request()->routeIs('withdraw.approval.index', 'withdraw.approval.salary.index');
    $documentReviewActive = request()->routeIs('withdraw.document-review.index');
    $approvalPendingCount = 0;
    $documentReviewPendingCount = 0;
    $hasApprovalAuthority = false;
    $isFinanceReviewer = false;
    $currentStaffId = trim((string) (session('staff_id') ?? auth()->user()?->user_id ?? ''));
    try {
        if ($currentStaffId !== '') {
            if (
                \Illuminate\Support\Facades\Schema::hasTable('withdraw')
                && \Illuminate\Support\Facades\Schema::hasTable('withdraw_approval_step')
            ) {
                $hasApprovalAuthority = \Illuminate\Support\Facades\DB::table('withdraw_approval_step')
                    ->where('withdraw_approval_staff_id', $currentStaffId)
                    ->whereIn('withdraw_approval_status', ['pending', 'approved'])
                    ->exists();
            }
            if ($hasApprovalAuthority) {
                $approvalPendingCount = app(\App\Http\Controllers\Withdraw\WithdrawGeneralController::class)
                    ->countReviewerPendingApprovals($currentStaffId);
            }
        }
    } catch (\Throwable $e) {
        $hasApprovalAuthority = false;
        $approvalPendingCount = 0;
    }
    try {
        $currentStaffId = trim((string) (session('staff_id') ?? auth()->user()?->user_id ?? ''));
        if (
            $currentStaffId !== ''
            && \Illuminate\Support\Facades\Schema::hasTable('withdraw')
            && \Illuminate\Support\Facades\Schema::hasTable('withdraw_set_finance_reviewer')
        ) {
            $isFinanceReviewer = \Illuminate\Support\Facades\DB::table('withdraw_set_finance_reviewer')
                ->where('withdraw_set_finance_reviewer_staff_id', $currentStaffId)
                ->where('withdraw_set_finance_reviewer_status', 1)
                ->exists();
            if ($isFinanceReviewer) {
                $documentReviewPendingCount = (int) \Illuminate\Support\Facades\DB::table('withdraw')
                    ->where('withdraw_status', 'pending_finance_review')
                    ->count();
            }
        }
    } catch (\Throwable $e) {
        $isFinanceReviewer = false;
        $documentReviewPendingCount = 0;
    }
    $isAdminUser = (string) session('pbm.system_role', '') === 'admin';
    $showSettingsSection = $isAdminUser && $canAccess($expenseChartMenuKey);
@endphp
<!-- sidenav -->
<aside class="sidenav-aside max-w-62.5 ease-nav-brand z-990 fixed inset-y-0 left-0 my-4 ml-4 flex h-[calc(100vh-2rem)] w-full -translate-x-[120%] flex-col overflow-hidden rounded-2xl border-0 bg-white p-0 antialiased shadow-none transition-transform duration-200 xl:my-0 xl:ml-0 xl:h-screen xl:translate-x-0 xl:rounded-none xl:bg-transparent motion-reduce:animate-none animate-in slide-in-from-left-6 fade-in-0 duration-500 fill-mode-both">
    <div class="h-19.5 shrink-0">
        <i class="absolute top-0 right-0 hidden p-4 opacity-50 cursor-pointer fas fa-times text-slate-400 xl:hidden" sidenav-close></i>
        <a class="block px-8 py-6 m-0 text-sm whitespace-nowrap text-slate-700" href="{{ route('withdraw.manage.index') }}">
            <img src="{{ asset('images/logo_phar_cmu_thai.png') }}" class="inline h-full max-w-full transition-all duration-200 ease-nav-brand max-h-8" alt="main_logo" />
            <span class="ml-1 font-semibold transition-all duration-200 ease-nav-brand">ระบบเบิกจ่ายงบประมาณ</span>
        </a>
    </div>

    <hr class="h-px shrink-0 mt-0 bg-transparent bg-gradient-to-r from-transparent via-black/40 to-transparent" />

    {{-- ใช้ [overflow-y:auto] แทน overflow-y-auto เพื่อไม่ให้ soft-ui perfect-scrollbar.js ผูก PerfectScrollbar แล้วทำให้เลื่อนไม่ได้ --}}
    <div class="min-h-0 flex-1 touch-pan-y overscroll-y-contain pb-6 [overflow-y:auto] [-webkit-overflow-scrolling:touch]">
        <ul class="flex flex-col pl-0 mb-0">
            @if ($canAccess('dashboard'))
                <li class="mt-0.5 w-full">
                    <a class="py-2.7 {{ request()->routeIs('dashboard') ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors" href="{{ route('dashboard') }}">
                        <div class="{{ request()->routeIs('dashboard') ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 items-center justify-center rounded-lg text-center xl:p-2.5">
                            <i class="fas fa-house text-sm {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-600' }}"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Dashboard</span>
                    </a>
                </li>
            @endif

            <li class="w-full mt-4">
                <h6 class="pl-6 ml-2 font-bold leading-tight uppercase text-xs opacity-60">{{ __('การเบิกจ่าย') }}</h6>
            </li>
            @if ($canAccess('withdraw-general'))
                <li class="mt-0.5 w-full">
                    <a class="py-2.7 {{ request()->routeIs('withdraw.general.*') ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors" href="{{ route('withdraw.general.index') }}">
                        <div class="{{ request()->routeIs('withdraw.general.*') ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 items-center justify-center rounded-lg text-center xl:p-2.5">
                            <i class="fas fa-list-check text-sm {{ request()->routeIs('withdraw.general.*') ? 'text-white' : 'text-slate-600' }}"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">เบิกจ่ายทั่วไป</span>
                    </a>
                </li>
            @endif
            @if ($canAccess('withdraw-project'))
                <li class="mt-0.5 w-full">
                    <a class="py-2.7 {{ request()->routeIs('withdraw.project.*') ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors" href="{{ route('withdraw.project.index') }}">
                        <div class="{{ request()->routeIs('withdraw.project.*') ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 items-center justify-center rounded-lg text-center xl:p-2.5">
                            <i class="fas fa-diagram-project text-sm {{ request()->routeIs('withdraw.project.*') ? 'text-white' : 'text-slate-600' }}"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">เบิกจ่ายเงินโครงการ</span>
                    </a>
                </li>
            @endif
            @if ($canAccess('withdraw-travel') || $canAccess('withdraw-salary'))
                <li class="mt-0.5 w-full">
                    <a class="py-2.7 {{ request()->routeIs('withdraw.travel.*') ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors" href="{{ route('withdraw.travel.index') }}">
                        <div class="{{ request()->routeIs('withdraw.travel.*') ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 items-center justify-center rounded-lg text-center xl:p-2.5">
                            <i class="fas fa-plane-departure text-sm {{ request()->routeIs('withdraw.travel.*') ? 'text-white' : 'text-slate-600' }}"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">เบิกจ่ายในการเดินทางฯ</span>
                    </a>
                </li>
            @endif
            @if ($canAccess('withdraw-salary'))
                <li class="mt-0.5 w-full">
                    <a class="py-2.7 {{ request()->routeIs('withdraw.salary.*') ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors" href="{{ route('withdraw.salary.index') }}">
                        <div class="{{ request()->routeIs('withdraw.salary.*') ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 items-center justify-center rounded-lg text-center xl:p-2.5">
                            <i class="fas fa-coins text-sm {{ request()->routeIs('withdraw.salary.*') ? 'text-white' : 'text-slate-600' }}"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">เบิกจ่ายเงินเดือน/ค่าจ้าง</span>
                    </a>
                </li>
            @endif

            @if (($canAccess('withdraw-general') || $canAccess('withdraw-project') || $canAccess('withdraw-salary')) && $hasApprovalAuthority)
                <li class="w-full mt-4">
                    <h6 class="pl-6 ml-2 font-bold leading-tight uppercase text-xs opacity-60">{{ __('การอนุมัติ') }}</h6>
                </li>
                <li class="mt-0.5 w-full">
                    <a class="py-2.7 {{ $approvalListActive ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors" href="{{ route('withdraw.approval.index') }}">
                        <div class="{{ $approvalListActive ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 items-center justify-center rounded-lg text-center xl:p-2.5">
                            <i class="fas fa-user-check text-sm {{ $approvalListActive ? 'text-white' : 'text-slate-600' }}"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">อนุมัติเบิกจ่าย</span>
                        <span id="approval-pending-count-badge" data-count="{{ (int) $approvalPendingCount }}"
                            class="ml-auto inline-flex min-w-6 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white shadow-sm {{ $approvalPendingCount > 0 ? '' : 'hidden' }}">
                            {{ number_format($approvalPendingCount) }}
                        </span>
                    </a>
                </li>
            @endif

            @if (($canAccess('withdraw-general') || $canAccess('withdraw-project') || $canAccess('withdraw-salary')) && $isFinanceReviewer)
                <li class="w-full mt-4">
                    <h6 class="pl-6 ml-2 font-bold leading-tight uppercase text-xs opacity-60">{{ __('ตรวจสอบเอกสาร') }}</h6>
                </li>
                <li class="mt-0.5 w-full">
                    <a class="py-2.7 {{ $documentReviewActive ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors" href="{{ route('withdraw.document-review.index') }}">
                        <div class="{{ $documentReviewActive ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 items-center justify-center rounded-lg text-center xl:p-2.5">
                            <i class="fas fa-file-circle-check text-sm {{ $documentReviewActive ? 'text-white' : 'text-slate-600' }}"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">ตรวจสอบเอกสารเบิกจ่าย</span>
                        <span id="document-review-pending-count-badge" data-count="{{ (int) $documentReviewPendingCount }}"
                            class="ml-auto inline-flex min-w-6 items-center justify-center rounded-full bg-sky-500 px-1.5 py-0.5 text-[10px] font-bold text-white shadow-sm {{ $documentReviewPendingCount > 0 ? '' : 'hidden' }}">
                            {{ number_format($documentReviewPendingCount) }}
                        </span>
                    </a>
                </li>
            @endif

            @if ($isAdminUser)
                <li class="w-full mt-4">
                    <h6 class="pl-6 ml-2 font-bold leading-tight uppercase text-xs opacity-60">{{ __('ระบบ') }}</h6>
                </li>
                <li class="mt-0.5 w-full">
                    <a class="py-2.7 {{ request()->routeIs('withdraw.usage-log.index') ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors" href="{{ route('withdraw.usage-log.index') }}">
                        <div class="{{ request()->routeIs('withdraw.usage-log.index') ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 items-center justify-center rounded-lg text-center xl:p-2.5">
                            <i class="fas fa-clipboard-list text-sm {{ request()->routeIs('withdraw.usage-log.index') ? 'text-white' : 'text-slate-600' }}"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">บันทึกการใช้งาน</span>
                    </a>
                </li>
            @endif

            @if ($showSettingsSection)
                <li class="mt-3 w-full px-3">
                    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-slate-100/90 shadow-sm">
                        <details
                            class="group"
                            @if ($settingsSectionOpen) open @endif
                            ontoggle="(function(d){var c=d.querySelector('[data-settings-chevron]');if(c)c.classList.toggle('rotate-180',d.open);})(this)"
                        >
                            <summary
                                class="flex cursor-pointer list-none items-center gap-2 px-3 py-2.5 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-200/50 [&::-webkit-details-marker]:hidden"
                            >
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-center text-slate-600 shadow-sm">
                                    <i class="fas fa-screwdriver-wrench text-sm"></i>
                                </span>
                                <span class="min-w-0 flex-1 leading-snug">ตั้งค่าข้อมูลพื้นฐาน</span>
                                <i
                                    data-settings-chevron
                                    class="fas fa-chevron-down ml-1 shrink-0 text-xs text-slate-500 transition-transform duration-200 {{ $settingsSectionOpen ? 'rotate-180' : '' }}"
                                ></i>
                            </summary>
                            <ul class="mb-1.5 mt-0 border-t border-slate-200/60 bg-slate-100/50 pb-1.5 pt-1">
                                <li class="mt-0.5 w-full">
                                    <a
                                        class="py-2.7 {{ $expenseChartActive ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors"
                                        href="{{ route('setting.index', ['module' => 'expenses']) }}"
                                    >
                                        <div class="{{ $expenseChartActive ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-center xl:p-2.5">
                                            <i class="fas fa-sitemap text-sm {{ $expenseChartActive ? 'text-white' : 'text-slate-600' }}"></i>
                                        </div>
                                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">ผังบัญชี (รายจ่าย)</span>
                                    </a>
                                </li>
                                <li class="mt-0.5 w-full">
                                    <a
                                        class="py-2.7 {{ $documentSettingActive ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors"
                                        href="{{ route('setting.index', ['module' => 'documents']) }}"
                                    >
                                        <div class="{{ $documentSettingActive ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-center xl:p-2.5">
                                            <i class="fas fa-file-lines text-sm {{ $documentSettingActive ? 'text-white' : 'text-slate-600' }}"></i>
                                        </div>
                                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">เอกสารประกอบการเบิกจ่าย</span>
                                    </a>
                                </li>
                                <li class="mt-0.5 w-full">
                                    <a
                                        class="py-2.7 {{ $bookNumberSettingActive ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors"
                                        href="{{ route('setting.index', ['module' => 'book-numbers']) }}"
                                    >
                                        <div class="{{ $bookNumberSettingActive ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-center xl:p-2.5">
                                            <i class="fas fa-hashtag text-sm {{ $bookNumberSettingActive ? 'text-white' : 'text-slate-600' }}"></i>
                                        </div>
                                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">เลขที่หนังสือ</span>
                                    </a>
                                </li>
                                <li class="mt-0.5 w-full">
                                    <a
                                        class="py-2.7 {{ $approverSettingActive ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors"
                                        href="{{ route('setting.index', ['module' => 'approvers']) }}"
                                    >
                                        <div class="{{ $approverSettingActive ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-center xl:p-2.5">
                                            <i class="fas fa-user-check text-sm {{ $approverSettingActive ? 'text-white' : 'text-slate-600' }}"></i>
                                        </div>
                                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">ผู้อนุมัติ</span>
                                    </a>
                                </li>
                                <li class="mt-0.5 w-full">
                                    <a
                                        class="py-2.7 {{ $financeReviewerSettingActive ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors"
                                        href="{{ route('setting.index', ['module' => 'finance-reviewers']) }}"
                                    >
                                        <div class="{{ $financeReviewerSettingActive ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-center xl:p-2.5">
                                            <i class="fas fa-money-check-dollar text-sm {{ $financeReviewerSettingActive ? 'text-white' : 'text-slate-600' }}"></i>
                                        </div>
                                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">ผู้ตรวจสอบ (การเงิน)</span>
                                    </a>
                                </li>
                                <li class="mt-0.5 w-full">
                                    <a
                                        class="py-2.7 {{ $memoTemplateSettingActive ? 'shadow-soft-xl bg-white font-semibold text-slate-700' : '' }} text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 transition-colors"
                                        href="{{ route('setting.memo-templates') }}"
                                    >
                                        <div class="{{ $memoTemplateSettingActive ? 'bg-gradient-to-tl from-green-600 to-lime-400 shadow-soft-2xl' : 'shadow-soft-2xl bg-white' }} mr-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-center xl:p-2.5">
                                            <i class="fas fa-file-lines text-sm {{ $memoTemplateSettingActive ? 'text-white' : 'text-slate-600' }}"></i>
                                        </div>
                                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">เทมเพลต (บันทึกข้อความ)</span>
                                    </a>
                                </li>
                            </ul>
                        </details>
                    </div>
                </li>
            @endif
        </ul>
    </div>
</aside>
@if ($hasApprovalAuthority)
    <script>
        (function () {
            const apiUrl = @json(url('/withdraw/api/general/list?scope=reviewer-pending'));
            const isApprovalMenuPendingRow = (row) => {
                const userStep = String(row?.user_step_status || '').trim().toLowerCase();
                if (userStep !== 'pending') return false;
                const rawStatus = String(row?.raw_status || '').trim();
                const statusLabel = String(row?.status || '').trim();
                if (rawStatus === 'pending_finance_review') return false;
                if (statusLabel.includes('รอการเงินตรวจสอบเอกสาร')) return false;
                return true;
            };
            const applyApprovalPendingCount = (pendingCount) => {
                const count = Number(pendingCount || 0);
                document.querySelectorAll('#approval-pending-count-badge').forEach((badge) => {
                    badge.textContent = String(count);
                    badge.dataset.count = String(count);
                    badge.classList.toggle('hidden', count <= 0);
                });
            };
            window.refreshWithdrawApprovalPendingBadge = async function () {
                if (!document.getElementById('approval-pending-count-badge')) return 0;
                try {
                    const response = await fetch(`${apiUrl}&_=${Date.now()}`, {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store',
                    });
                    const result = await response.json();
                    const rows = Array.isArray(result?.data) ? result.data : [];
                    const pendingCount = rows.filter(isApprovalMenuPendingRow).length;
                    applyApprovalPendingCount(pendingCount);
                    return pendingCount;
                } catch (error) {
                    return 0;
                }
            };
            const startApprovalBadgeSync = () => {
                window.refreshWithdrawApprovalPendingBadge();
                if (window.__withdrawApprovalBadgePollStarted) return;
                window.__withdrawApprovalBadgePollStarted = true;
                window.setInterval(() => {
                    if (document.visibilityState === 'visible') {
                        window.refreshWithdrawApprovalPendingBadge();
                    }
                }, 8000);
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        window.refreshWithdrawApprovalPendingBadge();
                    }
                });
                window.addEventListener('focus', () => {
                    window.refreshWithdrawApprovalPendingBadge();
                });
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startApprovalBadgeSync);
            } else {
                startApprovalBadgeSync();
            }
        })();
    </script>
@endif
<!-- end sidenav -->
