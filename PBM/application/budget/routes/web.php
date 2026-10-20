<?php

use App\Http\Controllers\Budget\BudgetLookupController;
use App\Http\Controllers\Budget\BudgetManageController;
use App\Http\Controllers\Budget\BudgetSettingController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/manage');
});

Route::prefix('budget')->group(function () {
    Route::get('/handoff-denied', function () {
        return response()->view('errors.handoff-denied', [], 403);
    })->name('handoff.denied');

    Route::middleware(['pbm.auth'])->group(function () {
        Route::get('/one-planning', [\App\Http\Controllers\Budget\OnePlanningController::class, 'index'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.one-planning.index');

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('dashboard');

        Route::get('/manage', [BudgetManageController::class, 'index'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.manage.index');
        Route::get('/manage/{budgetManageId}', [BudgetManageController::class, 'show'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.manage.show');

        Route::get('/api/manage/list', [BudgetManageController::class, 'apiList'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.manage.list');
        Route::get('/api/pay-list-options', [BudgetManageController::class, 'apiPayListOptions'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.pay-list-options');
        Route::get('/api/manage/{budgetManageId}/expenses', [BudgetManageController::class, 'apiExpenses'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.manage.expenses');
        Route::get('/api/manage/{budgetManageId}/pbm-projects', [BudgetManageController::class, 'apiPbmProjects'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.manage.pbm-projects');
        Route::get('/api/expense/{budgetExpensesId}/subs', [BudgetManageController::class, 'apiExpenseSubs'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.expense.subs');
        Route::get('/api/manage/{budgetManageId}/disbursements', [BudgetManageController::class, 'apiDisbursements'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.manage.disbursements');
        Route::get('/api/manage/{budgetManageId}/history', [BudgetManageController::class, 'apiHistory'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.manage.history');
        Route::post('/api/manage/save', [BudgetManageController::class, 'apiSaveManage'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.manage.save');
        Route::post('/api/manage/delete', [BudgetManageController::class, 'apiDeleteManage'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.manage.delete');
        Route::post('/api/manage-list/save', [BudgetManageController::class, 'apiSaveManageList'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.manage-list.save');
        Route::post('/api/manage-list/delete', [BudgetManageController::class, 'apiDeleteManageList'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.manage-list.delete');
        Route::post('/api/expense/save', [BudgetManageController::class, 'apiSaveExpense'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.expense.save');
        Route::post('/api/expense/delete', [BudgetManageController::class, 'apiDeleteExpense'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.expense.delete');
        Route::post('/api/expense-sub/save', [BudgetManageController::class, 'apiSaveExpenseSub'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.expense-sub.save');
        Route::post('/api/expense-sub/delete', [BudgetManageController::class, 'apiDeleteExpenseSub'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.expense-sub.delete');
        Route::post('/api/manage/finalize-plan', [BudgetManageController::class, 'apiFinalizePlan'])
            ->middleware('pbm.role:member,admin')
            ->name('budget.api.manage.finalize-plan');

        Route::get('/api/cascade/event-by-plan/{planCode}', [BudgetLookupController::class, 'eventsByPlan'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.cascade.events-by-plan');
        Route::get('/api/cascade/activity-by-event/{eventCode}', [BudgetLookupController::class, 'activitiesByEvent'])
            ->middleware('pbm.role:viewer,member,admin')
            ->name('budget.api.cascade.activities-by-event');

        Route::prefix('settings')->middleware('pbm.role:admin')->group(function () {
            Route::get('/year', [BudgetSettingController::class, 'yearIndex'])->name('budget.settings.year.index');
            Route::get('/plan', [BudgetSettingController::class, 'planIndex'])->name('budget.settings.plan.index');
            Route::get('/event', [BudgetSettingController::class, 'eventIndex'])->name('budget.settings.event.index');
            Route::get('/activity', [BudgetSettingController::class, 'activityIndex'])->name('budget.settings.activity.index');
            Route::get('/fund', [BudgetSettingController::class, 'fundIndex'])->name('budget.settings.fund.index');
            Route::get('/cost', [BudgetSettingController::class, 'costIndex'])->name('budget.settings.cost.index');
            Route::get('/source', [BudgetSettingController::class, 'sourceIndex'])->name('budget.settings.source.index');
            Route::get('/type', [BudgetSettingController::class, 'typeIndex'])->name('budget.settings.type.index');
            Route::get('/category-budget', [BudgetSettingController::class, 'categoryBudgetIndex'])->name('budget.settings.category-budget.index');
            Route::get('/category-pay', [BudgetSettingController::class, 'categoryPayIndex'])->name('budget.settings.category-pay.index');
            Route::get('/pay-list', [BudgetSettingController::class, 'payListIndex'])->name('budget.settings.pay-list.index');
            Route::get('/expenses', [BudgetSettingController::class, 'expensesIndex'])->name('budget.settings.expenses.index');

            Route::get('/api/options', [BudgetSettingController::class, 'apiOptions'])->name('budget.settings.api.options');
            Route::get('/api/{module}', [BudgetSettingController::class, 'apiList'])->name('budget.settings.api.list');
            Route::post('/api/{module}/save', [BudgetSettingController::class, 'apiSave'])->name('budget.settings.api.save');
            Route::post('/api/{module}/delete', [BudgetSettingController::class, 'apiDelete'])->name('budget.settings.api.delete');
            Route::post('/api/{module}/toggle', [BudgetSettingController::class, 'apiToggle'])->name('budget.settings.api.toggle');
        });
    });
});
