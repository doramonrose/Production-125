<?php

use App\Http\Controllers\Withdraw\WithdrawLookupController;
use App\Http\Controllers\Withdraw\WithdrawManageController;
use App\Http\Controllers\Withdraw\WithdrawGeneralController;
use App\Http\Controllers\Withdraw\WithdrawProjectExternalApiController;
use App\Http\Controllers\Withdraw\WithdrawProjectHandoffController;
use App\Http\Controllers\Withdraw\WithdrawProjectProcurementController;
use App\Http\Controllers\Withdraw\WithdrawSalaryController;
use App\Http\Controllers\Withdraw\WithdrawTravelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsageLogController;
use App\Http\Controllers\Setting\WithdrawSettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/general');
});

Route::prefix('withdraw')->group(function () {
    Route::get('/handoff-denied', function () {
        return response()->view('errors.handoff-denied', [], 403);
    })->name('handoff.denied');

    Route::match(['get', 'post'], '/project/from-project', [WithdrawProjectHandoffController::class, 'accept'])
        ->name('withdraw.project.from-project');

    Route::post('/travel/from-leaves', [WithdrawTravelController::class, 'accept'])
        ->name('withdraw.travel.from-leaves');
    Route::get('/api/travel/by-leaves', [WithdrawTravelController::class, 'byLeaves'])
        ->name('withdraw.travel.by-leaves');

    Route::prefix('api/project/external')->group(function () {
        Route::get('/withdraws', [WithdrawProjectExternalApiController::class, 'index'])
            ->name('withdraw.project.external.withdraws');
        Route::get('/pending-confirms', [WithdrawProjectExternalApiController::class, 'pendingConfirms'])
            ->name('withdraw.project.external.pending-confirms');
        Route::get('/memo-pdf', [WithdrawProjectExternalApiController::class, 'memoPdf'])
            ->name('withdraw.project.external.memo-pdf');
        Route::get('/approve-memo-pdf', [WithdrawProjectExternalApiController::class, 'approveMemoPdf'])
            ->name('withdraw.project.external.approve-memo-pdf');
        Route::get('/print-pdf', [WithdrawProjectExternalApiController::class, 'printPdf'])
            ->name('withdraw.project.external.print-pdf');
        Route::get('/file/{id}', [WithdrawProjectExternalApiController::class, 'file'])
            ->whereNumber('id')
            ->name('withdraw.project.external.file');
        Route::get('/procurement-pdf/{id}', [WithdrawProjectExternalApiController::class, 'procurementPdf'])
            ->whereNumber('id')
            ->name('withdraw.project.external.procurement-pdf');
        Route::get('/procurement-file/{id}', [WithdrawProjectExternalApiController::class, 'procurementFile'])
            ->whereNumber('id')
            ->name('withdraw.project.external.procurement-file');
    });

    Route::middleware(['pbm.auth'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('dashboard');

        Route::get('/manage', [WithdrawManageController::class, 'index'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.manage.index');
        Route::get('/manage/{budgetManageId}', [WithdrawManageController::class, 'show'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.manage.show');

        Route::get('/general', [WithdrawGeneralController::class, 'index'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.index');
        Route::get('/project', [WithdrawGeneralController::class, 'index'])
            ->middleware('pbm.role:viewer,member,admin')
            ->defaults('withdraw_kind', 'project')
            ->name('withdraw.project.index');
        Route::get('/api/project/handoff/{token}', [WithdrawProjectHandoffController::class, 'show'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.project.api.handoff');
        Route::get('/api/project/handoffs/pending', [WithdrawProjectHandoffController::class, 'pending'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.project.api.handoffs.pending');
        Route::post('/api/project/handoffs/recreate', [WithdrawProjectHandoffController::class, 'recreate'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.project.api.handoffs.recreate');
        Route::post('/api/project/handoffs/{token}/dismiss', [WithdrawProjectHandoffController::class, 'dismiss'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.project.api.handoffs.dismiss');
        Route::get('/api/project/amount', [WithdrawGeneralController::class, 'apiProjectAmount'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.project.api.amount');
        Route::get('/api/project/approve-memo-pdf', [WithdrawGeneralController::class, 'projectApproveMemoPdf'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.project.api.approve-memo-pdf');
        Route::get('/api/project/print-pdf', [WithdrawGeneralController::class, 'projectPrintPdf'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.project.api.print-pdf');
        Route::post('/api/project/procurement-pdf', [WithdrawProjectProcurementController::class, 'pdf'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.project.api.procurement-pdf');
        Route::get('/api/project/procurement', [WithdrawProjectProcurementController::class, 'list'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.project.api.procurement.list');
        Route::get('/api/project/procurement/winners', [WithdrawProjectProcurementController::class, 'winners'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.project.api.procurement.winners');
        Route::post('/api/project/procurement', [WithdrawProjectProcurementController::class, 'save'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.project.api.procurement.save');
        Route::get('/api/project/procurement/file/{id}', [WithdrawProjectProcurementController::class, 'file'])
            ->middleware('pbm.role:viewer,member,admin')
            ->whereNumber('id')
            ->name('withdraw.project.api.procurement.file');
        Route::get('/api/project/procurement/{id}/pdf', [WithdrawProjectProcurementController::class, 'pdfById'])
            ->middleware('pbm.role:viewer,member,admin')
            ->whereNumber('id')
            ->name('withdraw.project.api.procurement.pdf');
        Route::get('/api/project/procurement/{id}', [WithdrawProjectProcurementController::class, 'show'])
            ->middleware('pbm.role:viewer,member,admin')
            ->whereNumber('id')
            ->name('withdraw.project.api.procurement.show');
        Route::get('/project/{id}/memo-pdf', [WithdrawGeneralController::class, 'memoPdf'])
            ->middleware('pbm.role:viewer,member,admin')
            ->whereNumber('id')
            ->name('withdraw.project.memo-pdf');
        Route::get('/general/{id}/memo-pdf', [WithdrawGeneralController::class, 'memoPdf'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.memo-pdf');
        Route::get('/general/{id}/memo-finance-pdf', [WithdrawGeneralController::class, 'memoFinancePdf'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.memo-finance-pdf');
        Route::get('/project/{id}/memo-finance-pdf', [WithdrawGeneralController::class, 'memoFinancePdf'])
            ->middleware('pbm.role:viewer,member,admin')
            ->whereNumber('id')
            ->name('withdraw.project.memo-finance-pdf');
        Route::get('/approval', [WithdrawGeneralController::class, 'index'])
            ->middleware('pbm.role:viewer,member,admin')
            ->defaults('scope', 'reviewer-pending')
            ->name('withdraw.approval.index');
        Route::get('/approval/general', function (\Illuminate\Http\Request $request) {
            return redirect()->route('withdraw.approval.index', $request->query());
        });
        Route::get('/document-review', [WithdrawGeneralController::class, 'index'])
            ->middleware('pbm.role:viewer,member,admin')
            ->defaults('scope', 'document-review')
            ->name('withdraw.document-review.index');
        Route::get('/document-review/general', function (\Illuminate\Http\Request $request) {
            return redirect()->route('withdraw.document-review.index', $request->query());
        });
        Route::get('/api/general/pay-list-options', [WithdrawGeneralController::class, 'apiPayListOptions'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.api.pay-list-options');
        Route::get('/api/general/document-options', [WithdrawGeneralController::class, 'apiDocumentOptions'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.api.document-options');
        Route::get('/api/general/expense-chart-options', [WithdrawGeneralController::class, 'apiExpenseChartOptions'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.api.expense-chart-options');
        Route::get('/api/general/budget-source-options', [WithdrawGeneralController::class, 'apiBudgetSourceOptions'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.api.budget-source-options');
        Route::get('/api/general/list', [WithdrawGeneralController::class, 'apiList'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.api.list');
        Route::get('/api/general/next-running-no', [WithdrawGeneralController::class, 'apiNextRunningNo'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.api.next-running-no');
        Route::post('/api/general/save-draft', [WithdrawGeneralController::class, 'apiSaveDraft'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.api.save-draft');
        Route::get('/api/general/signature-proxy', [WithdrawGeneralController::class, 'apiSignatureProxy'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.api.signature-proxy');
        Route::get('/api/general/{id}', [WithdrawGeneralController::class, 'apiDetail'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.api.detail');
        Route::get('/api/general/{id}/file', [WithdrawGeneralController::class, 'apiFile'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.api.file');
        Route::post('/api/general/delete', [WithdrawGeneralController::class, 'apiDelete'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.api.delete');
        Route::post('/api/general/proceed', [WithdrawGeneralController::class, 'apiProceed'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.general.api.proceed');

        Route::get('/travel', [WithdrawTravelController::class, 'index'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.travel.index');
        Route::get('/travel/{id}/memo-pdf', [WithdrawSalaryController::class, 'memoPdf'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.travel.memo-pdf');
        Route::get('/api/travel/file', [WithdrawTravelController::class, 'file'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.travel.api.file');
        Route::get('/api/travel/form', [WithdrawTravelController::class, 'form'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.travel.api.form');
        Route::get('/api/travel/pending', [WithdrawTravelController::class, 'pending'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.travel.api.pending');
        Route::post('/api/travel/pending/{token}/cancel', [WithdrawTravelController::class, 'cancel'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.travel.api.pending.cancel');
        Route::get('/api/travel/records', [WithdrawTravelController::class, 'records'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.travel.api.records');
        Route::post('/api/travel/delete', [WithdrawTravelController::class, 'destroy'])
            ->middleware('pbm.role:admin')
            ->name('withdraw.travel.api.delete');
        Route::post('/api/travel/cancel', [WithdrawTravelController::class, 'cancelRecord'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.travel.api.cancel');
        Route::get('/api/travel/handoff/{token}', [WithdrawTravelController::class, 'show'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.travel.api.handoff');

        Route::get('/salary', [WithdrawSalaryController::class, 'index'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.index');
        Route::get('/salary/{id}/memo-pdf', [WithdrawSalaryController::class, 'memoPdf'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.memo-pdf');
        Route::get('/approval/salary', function (\Illuminate\Http\Request $request) {
            return redirect()->route('withdraw.approval.index', $request->query());
        })->name('withdraw.approval.salary.index');
        Route::get('/api/salary/pay-list-options', [WithdrawSalaryController::class, 'apiPayListOptions'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.pay-list-options');
        Route::get('/api/salary/document-options', [WithdrawSalaryController::class, 'apiDocumentOptions'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.document-options');
        Route::get('/api/salary/expense-chart-options', [WithdrawSalaryController::class, 'apiExpenseChartOptions'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.expense-chart-options');
        Route::get('/api/salary/budget-source-options', [WithdrawSalaryController::class, 'apiBudgetSourceOptions'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.budget-source-options');
        Route::get('/api/salary/list', [WithdrawSalaryController::class, 'apiList'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.list');
        Route::get('/api/salary/next-running-no', [WithdrawSalaryController::class, 'apiNextRunningNo'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.next-running-no');
        Route::post('/api/salary/save-draft', [WithdrawSalaryController::class, 'apiSaveDraft'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.save-draft');
        Route::get('/api/salary/signature-proxy', [WithdrawSalaryController::class, 'apiSignatureProxy'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.signature-proxy');
        Route::get('/api/salary/{id}', [WithdrawSalaryController::class, 'apiDetail'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.detail');
        Route::get('/api/salary/{id}/file', [WithdrawSalaryController::class, 'apiFile'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.file');
        Route::post('/api/salary/delete', [WithdrawSalaryController::class, 'apiDelete'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.delete');
        Route::post('/api/salary/proceed', [WithdrawSalaryController::class, 'apiProceed'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.proceed');
        Route::post('/api/salary/resend-notification', [WithdrawSalaryController::class, 'apiResendApprovalNotification'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.resend-notification');
        Route::get('/api/salary/{id}/one-planning-preview', [WithdrawSalaryController::class, 'apiOnePlanningPreview'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.one-planning-preview');
        Route::post('/api/salary/{id}/one-planning-send', [WithdrawSalaryController::class, 'apiOnePlanningSend'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.salary.api.one-planning-send');

        Route::get('/api/manage/list', [WithdrawManageController::class, 'apiList'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.manage.list');
        Route::get('/api/manage/{budgetManageId}/expenses', [WithdrawManageController::class, 'apiExpenses'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.manage.expenses');
        Route::get('/api/expense/{budgetExpensesId}/subs', [WithdrawManageController::class, 'apiExpenseSubs'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.expense.subs');
        Route::get('/api/manage/{budgetManageId}/history', [WithdrawManageController::class, 'apiHistory'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.manage.history');
        Route::post('/api/manage/save', [WithdrawManageController::class, 'apiSaveManage'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.manage.save');
        Route::post('/api/manage/delete', [WithdrawManageController::class, 'apiDeleteManage'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.manage.delete');
        Route::post('/api/manage-list/save', [WithdrawManageController::class, 'apiSaveManageList'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.manage-list.save');
        Route::post('/api/manage-list/delete', [WithdrawManageController::class, 'apiDeleteManageList'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.manage-list.delete');
        Route::post('/api/expense/save', [WithdrawManageController::class, 'apiSaveExpense'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.expense.save');
        Route::post('/api/expense/delete', [WithdrawManageController::class, 'apiDeleteExpense'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.expense.delete');
        Route::post('/api/expense-sub/save', [WithdrawManageController::class, 'apiSaveExpenseSub'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.expense-sub.save');
        Route::post('/api/expense-sub/delete', [WithdrawManageController::class, 'apiDeleteExpenseSub'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.expense-sub.delete');
        Route::post('/api/manage/finalize-plan', [WithdrawManageController::class, 'apiFinalizePlan'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.manage.finalize-plan');

        Route::get('/api/memo-template', [WithdrawSettingController::class, 'apiMemoTemplateResolve'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('withdraw.api.memo-template');

        Route::get('/api/cascade/event-by-plan/{planCode}', [WithdrawLookupController::class, 'eventsByPlan'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.cascade.events-by-plan');
        Route::get('/api/cascade/activity-by-event/{eventCode}', [WithdrawLookupController::class, 'activitiesByEvent'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.cascade.activities-by-event');

        Route::get('/usage-log', [UsageLogController::class, 'index'])
            ->middleware('pbm.role:admin')
            ->name('withdraw.usage-log.index');

        Route::prefix('setting')->middleware('pbm.role:admin')->group(function () {
            Route::get('/api/options', [WithdrawSettingController::class, 'apiOptions'])->name('setting.api.options');
            Route::get('/api/memo-templates/catalog', [WithdrawSettingController::class, 'apiMemoTemplatesCatalog'])
                ->name('setting.api.memo-templates.catalog');
            Route::get('/api/memo-templates/resolve', [WithdrawSettingController::class, 'apiMemoTemplateResolve'])
                ->name('setting.api.memo-templates.resolve');
            Route::post('/api/memo-templates/save', [WithdrawSettingController::class, 'apiMemoTemplateSave'])
                ->name('setting.api.memo-templates.save');
            Route::get('/memo-templates', [WithdrawSettingController::class, 'memoTemplatesIndex'])
                ->name('setting.memo-templates');
            Route::get('/api/{module}', [WithdrawSettingController::class, 'apiList'])
                ->whereIn('module', ['expenses', 'documents', 'book-numbers', 'approvers', 'finance-reviewers'])
                ->name('setting.api.list');
            Route::post('/api/{module}/save', [WithdrawSettingController::class, 'apiSave'])
                ->whereIn('module', ['expenses', 'documents', 'book-numbers', 'approvers', 'finance-reviewers'])
                ->name('setting.api.save');
            Route::post('/api/{module}/delete', [WithdrawSettingController::class, 'apiDelete'])
                ->whereIn('module', ['expenses', 'documents', 'book-numbers', 'approvers', 'finance-reviewers'])
                ->name('setting.api.delete');
            Route::post('/api/{module}/toggle', [WithdrawSettingController::class, 'apiToggle'])
                ->whereIn('module', ['expenses', 'documents', 'book-numbers', 'approvers', 'finance-reviewers'])
                ->name('setting.api.toggle');
            Route::post('/api/approvers/toggle-notification', [WithdrawSettingController::class, 'apiToggleApproverNotification'])
                ->name('setting.api.approvers.toggle-notification');

            Route::get('/{module}', [WithdrawSettingController::class, 'settingIndex'])
                ->whereIn('module', ['expenses', 'documents', 'book-numbers', 'approvers', 'finance-reviewers'])
                ->name('setting.index');
        });
    });
});
