<?php

return [
    'menus' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fas fa-house'],
        ['key' => 'withdraw-general', 'label' => 'รายการเบิกจ่ายทั่วไป', 'route' => 'withdraw.general.index', 'icon' => 'fas fa-list-check'],
        ['key' => 'withdraw-project', 'label' => 'รายการเบิกจ่ายเงินโครงการ', 'route' => 'withdraw.project.index', 'icon' => 'fas fa-diagram-project'],
        ['key' => 'withdraw-salary', 'label' => 'รายการเบิกจ่ายเงินเดือน/ค่าจ้าง', 'route' => 'withdraw.salary.index', 'icon' => 'fas fa-coins'],
        ['key' => 'withdraw-settings-expenses', 'label' => 'ผังบัญชี (รายจ่าย)', 'route' => 'setting.index', 'icon' => 'fas fa-sitemap'],
    ],
];
