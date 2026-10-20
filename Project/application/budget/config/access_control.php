<?php

return [
    'menus' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fas fa-house'],
        ['key' => 'budget-manage', 'label' => 'จัดการงบประมาณ', 'route' => 'budget.manage.index', 'icon' => 'fas fa-coins'],
        ['key' => 'budget-settings-year', 'label' => 'ปีงบประมาณ', 'route' => 'budget.settings.year.index', 'icon' => 'fas fa-calendar'],
        ['key' => 'budget-settings-source', 'label' => 'แหล่งงบ', 'route' => 'budget.settings.source.index', 'icon' => 'fas fa-landmark'],
        ['key' => 'budget-settings-type', 'label' => 'ประเภท', 'route' => 'budget.settings.type.index', 'icon' => 'fas fa-layer-group'],
        ['key' => 'budget-settings-plan', 'label' => 'แผนงาน', 'route' => 'budget.settings.plan.index', 'icon' => 'fas fa-diagram-project'],
        ['key' => 'budget-settings-event', 'label' => 'งาน-กิจกรรม', 'route' => 'budget.settings.event.index', 'icon' => 'fas fa-list-check'],
        ['key' => 'budget-settings-fund', 'label' => 'กองทุน', 'route' => 'budget.settings.fund.index', 'icon' => 'fas fa-wallet'],
        ['key' => 'budget-settings-cost', 'label' => 'ต้นทุน', 'route' => 'budget.settings.cost.index', 'icon' => 'fas fa-tags'],
        ['key' => 'budget-settings-category-budget', 'label' => 'หมวดงบ', 'route' => 'budget.settings.category-budget.index', 'icon' => 'fas fa-folder-tree'],
        ['key' => 'budget-settings-category-pay', 'label' => 'หมวดรายจ่าย', 'route' => 'budget.settings.category-pay.index', 'icon' => 'fas fa-file-invoice-dollar'],
        ['key' => 'budget-settings-pay-list', 'label' => 'รายการจ่าย', 'route' => 'budget.settings.pay-list.index', 'icon' => 'fas fa-list-ul'],
        ['key' => 'budget-settings-expenses', 'label' => 'รายจ่าย', 'route' => 'budget.settings.expenses.index', 'icon' => 'fas fa-sitemap'],
    ],
];
