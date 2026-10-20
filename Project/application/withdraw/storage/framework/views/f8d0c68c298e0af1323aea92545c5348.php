<?php
    $user = auth()->user();
    $userName = $user?->name_th ?: $user?->name ?? __('User');
    $userAvatar = trim((string) ($user?->image_url ?? (session('staff_photo_url') ?? '')));
    $mainMenuUrl = url('/dashboard');
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName() ?? 'page';
    $settingModule = (string) request()->route('module');
    $pageTitles = [
        'dashboard' => 'Dashboard',
        'withdraw.manage.index' => 'บริหารงบประมาณ',
        'withdraw.manage.show' => 'รายละเอียดงบประมาณ',
        'withdraw.general.index' => 'รายการเบิกจ่ายทั่วไป',
        'withdraw.project.index' => 'รายการเบิกจ่ายเงินโครงการ',
        'withdraw.usage-log.index' => 'บันทึกการใช้งาน',
        'setting.index' => $settingModule === 'documents'
            ? 'เอกสารประกอบการเบิกจ่าย'
            : ($settingModule === 'book-numbers'
                ? 'เลขที่หนังสือ'
                : ($settingModule === 'approvers'
                    ? 'ผู้อนุมัติ'
                    : ($settingModule === 'finance-reviewers' ? 'ผู้ตรวจสอบ (การเงิน)' : 'ผังบัญชี (รายจ่าย)'))),
    ];
    $pageTitle = $title ?? ($pageTitles[$routeName] ?? 'หน้าระบบ');
    $canChangePassword = \Illuminate\Support\Facades\Route::has('password.update');
    $canLogout = \Illuminate\Support\Facades\Route::has('logout');
?>
<nav class="animate-in slide-in-from-top-4 fade-in-0 duration-500 fill-mode-both relative z-50 flex flex-nowrap items-center justify-between px-0 py-2 mx-3 transition-all shadow-none duration-250 ease-soft-in rounded-2xl sm:mx-6 lg:justify-start"
    navbar-main navbar-scroll="true">
    <div class="flex min-w-0 w-full items-center justify-between gap-2 px-3 py-1 mx-auto sm:px-4">
        <nav class="min-w-0 flex-1 overflow-hidden">
            <ol class="flex min-w-0 flex-nowrap items-center pt-1 mr-0 bg-transparent rounded-lg sm:mr-16">
                <li class="shrink-0 leading-normal text-sm">
                    <a class="opacity-50 text-slate-700" href="<?php echo e(route('withdraw.manage.index')); ?>">หน้าหลัก</a>
                </li>
                <li class="min-w-0 truncate text-sm pl-2 capitalize leading-normal text-slate-700 before:pr-2 before:text-gray-600 before:content-['/']"
                    aria-current="page">
                    <?php echo e($pageTitle); ?>

                </li>
            </ol>
        </nav>

        <div class="flex shrink-0 items-center sm:mr-6 md:mr-0">
            <div class="flex items-center md:ml-auto md:pr-4"></div>
            <ul class="flex flex-row items-center justify-end pl-0 mb-0 list-none">
                <li class="flex items-center">
                    <div class="flex items-center px-0 py-1 font-semibold transition-all ease-nav-brand text-sm text-slate-500 sm:py-2">
                        <?php if($userAvatar !== ''): ?>
                            <img src="<?php echo e($userAvatar); ?>" alt="<?php echo e($userName); ?>"
                                class="inline-block h-8 w-8 rounded-full object-cover shadow-md sm:mr-1 sm:h-9 sm:w-9"
                                referrerpolicy="no-referrer" />
                        <?php else: ?>
                            <i class="fa fa-user sm:mr-1"></i>
                        <?php endif; ?>
                        <span class="hidden sm:inline"><?php echo e($userName); ?></span>
                        <a href="<?php echo e($mainMenuUrl); ?>"
                            class="wd-main-menu-btn group ml-1.5 inline-flex shrink-0 items-center gap-1 rounded-full border border-slate-200 bg-white/90 px-2 py-1.5 text-xs font-semibold text-slate-700 shadow-soft-2xl transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white hover:shadow-md sm:ml-2 sm:gap-1.5 sm:px-2.5 sm:py-2.5"
                            data-ui-tooltip="กลับเมนูหลัก" data-ui-tooltip-placement="bottom" aria-label="กลับเมนูหลัก">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors group-hover:bg-lime-100 group-hover:text-lime-700">
                                <i class="fas fa-house text-[10px]"></i>
                            </span>
                            <span class="wd-main-menu-label hidden sm:inline whitespace-nowrap">เมนูหลัก</span>
                        </a>
                    </div>
                </li>

                <li class="flex items-center pl-2 sm:pl-4 xl:hidden">
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

                <?php if($canLogout): ?>
                    <li class="flex items-center pl-4">
                        <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-logout' }))"
                            class="p-0 transition-all text-sm ease-nav-brand text-slate-500 hover:text-slate-700"
                            data-ui-tooltip="ออกจากระบบ" data-ui-tooltip-placement="bottom" aria-label="ออกจากระบบ">
                            <i class="fas fa-right-from-bracket"></i>
                        </button>
                    </li>
                <?php endif; ?>

                <?php if($canChangePassword): ?>
                    <li class="flex items-center px-4">
                        <button type="button" id="btn-open-change-password"
                            onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'change-password' }))"
                            class="p-0 transition-all text-sm ease-nav-brand text-slate-500 hover:text-slate-700"
                            data-ui-tooltip="แก้ไขรหัสผ่าน" data-ui-tooltip-placement="bottom" aria-label="แก้ไขรหัสผ่าน">
                            <i class="cursor-pointer fa fa-cog"></i>
                        </button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
    @media (max-width: 1023px) {
        nav[navbar-main] {
            flex-wrap: nowrap;
            margin-left: 0.75rem;
            margin-right: 0.75rem;
        }

        nav[navbar-main] > div {
            flex-wrap: nowrap;
            min-width: 0;
            gap: 0.5rem;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        nav[navbar-main] > div > nav {
            min-width: 0;
            flex: 1 1 auto;
            overflow: hidden;
        }

        nav[navbar-main] > div > nav ol {
            flex-wrap: nowrap;
            margin-right: 0;
            min-width: 0;
        }

        nav[navbar-main] > div > nav ol li:last-child {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        nav[navbar-main] > div > div {
            flex: 0 0 auto;
            margin-top: 0;
            width: auto;
        }

        nav[navbar-main] ul {
            width: auto;
        }

        nav[navbar-main] .wd-main-menu-label {
            display: none !important;
        }

        nav[navbar-main] .wd-main-menu-btn {
            padding: 0.4rem;
            gap: 0;
        }
    }
</style>

<?php echo $__env->renderWhen($canChangePassword, 'layouts.partials.modal-change-password', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1])); ?>

<?php if($canLogout): ?>
    <?php if (isset($component)) { $__componentOriginal2cfaf2d8c559a20e3495c081df2d0b10 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2cfaf2d8c559a20e3495c081df2d0b10 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.confirm-modal','data' => ['name' => 'confirm-logout','action' => route('logout'),'title' => 'ยืนยันออกจากระบบ?','message' => 'คุณต้องการออกจากระบบใช่หรือไม่','confirmText' => 'ออกจากระบบ','confirmVariant' => 'danger','icon' => 'fas fa-right-from-bracket']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('confirm-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'confirm-logout','action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('logout')),'title' => 'ยืนยันออกจากระบบ?','message' => 'คุณต้องการออกจากระบบใช่หรือไม่','confirmText' => 'ออกจากระบบ','confirmVariant' => 'danger','icon' => 'fas fa-right-from-bracket']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2cfaf2d8c559a20e3495c081df2d0b10)): ?>
<?php $attributes = $__attributesOriginal2cfaf2d8c559a20e3495c081df2d0b10; ?>
<?php unset($__attributesOriginal2cfaf2d8c559a20e3495c081df2d0b10); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2cfaf2d8c559a20e3495c081df2d0b10)): ?>
<?php $component = $__componentOriginal2cfaf2d8c559a20e3495c081df2d0b10; ?>
<?php unset($__componentOriginal2cfaf2d8c559a20e3495c081df2d0b10); ?>
<?php endif; ?>
<?php endif; ?>
<?php /**PATH /var/www/application/withdraw/resources/views/layouts/partials/navbar.blade.php ENDPATH**/ ?>