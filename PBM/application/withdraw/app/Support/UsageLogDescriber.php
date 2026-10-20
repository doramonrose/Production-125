<?php

namespace App\Support;

class UsageLogDescriber
{
    /**
     * @param  array<string, mixed>  $routeParameters
     */
    public function describe(string $method, ?string $routeName, string $path, array $routeParameters = [], array $hints = []): string
    {
        $method = strtoupper($method);
        $routeName = trim((string) $routeName);
        $subject = $this->subject($routeParameters, $hints);

        $mapped = $this->routeLabel($routeName, $hints);
        if ($mapped !== null) {
            if (in_array($routeName, ['withdraw.general.api.list', 'withdraw.salary.api.list'], true)) {
                $scope = trim((string) ($hints['scope'] ?? ''));

                return match ($scope) {
                    'เฉพาะรายการที่รออนุมัติ' => 'เปิดหน้ารายการที่รออนุมัติ',
                    'เฉพาะรายการที่รอตรวจสอบเอกสาร' => 'เปิดหน้ารายการที่รอตรวจสอบเอกสาร',
                    default => $this->applyTypeLabel('เปิดหน้ารายการเบิกจ่าย', $hints),
                };
            }

            $mapped = $this->applyTypeLabel($mapped, $hints);
            $scope = trim((string) ($hints['scope'] ?? ''));
            if ($scope !== '') {
                $mapped .= ' · '.$scope;
            }

            return $subject !== '' ? $mapped.' '.$subject : $mapped;
        }

        $verb = match ($method) {
            'POST' => 'บันทึกหรือดำเนินการ',
            'PUT', 'PATCH' => 'แก้ไข',
            'DELETE' => 'ลบ',
            default => str_contains($path, '/api/') ? 'เรียกดูข้อมูล' : 'เปิดหน้า',
        };

        $place = $routeName !== '' ? $routeName : $path;

        return trim($verb.' '.$place.' '.$subject);
    }

    /**
     * @param  array<string, mixed>  $routeParameters
     */
    private function subject(array $routeParameters, array $hints): string
    {
        $hintId = $hints['id'] ?? '';
        if (! is_array($hintId) && ! is_object($hintId)) {
            $hintId = trim((string) $hintId);
            if ($hintId !== '') {
                $routeParameters['id'] = $routeParameters['id'] ?? $hintId;
            }
        }

        foreach (['id', 'budgetManageId', 'budgetExpensesId', 'module'] as $key) {
            $raw = $routeParameters[$key] ?? '';
            if (is_array($raw) || is_object($raw)) {
                continue;
            }
            $value = trim((string) $raw);
            if ($value === '' || ($key !== 'module' && ctype_digit($value) && (int) $value <= 0)) {
                continue;
            }
            if (! empty($hints['has_record']) && $key !== 'module') {
                continue;
            }
            if ($key === 'module') {
                return '('.$this->moduleLabel($value).')';
            }

            return '#'.$value;
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $hints
     */
    private function applyTypeLabel(string $label, array $hints): string
    {
        $typeLabel = trim((string) ($hints['type_label'] ?? ''));
        if ($typeLabel === '' || str_contains($label, $typeLabel)) {
            return $label;
        }
        if (str_contains($label, 'รายการเบิกจ่าย')) {
            return str_replace('รายการเบิกจ่าย', 'รายการ'.$typeLabel, $label);
        }

        return $label;
    }

    private function moduleLabel(string $module): string
    {
        return match ($module) {
            'expenses' => 'ผังบัญชี',
            'documents' => 'เอกสารประกอบ',
            'book-numbers' => 'เลขที่หนังสือ',
            'approvers' => 'ผู้อนุมัติ',
            'finance-reviewers' => 'ผู้ตรวจสอบการเงิน',
            default => $module,
        };
    }

    private function routeLabel(string $routeName, array $hints): ?string
    {
        if (in_array($routeName, ['withdraw.general.api.proceed', 'withdraw.salary.api.proceed'], true)) {
            $action = trim((string) ($hints['action'] ?? ''));

            return match ($action) {
                'cancel_proceed' => 'ยกเลิกรายการเบิกจ่าย',
                'request_revision' => 'ส่งกลับให้แก้ไข',
                'request_revision_to_finance' => 'ส่งกลับให้งานการเงินแก้ไข',
                default => 'ดำเนินการหรืออนุมัติรายการเบิกจ่าย',
            };
        }

        return match ($routeName) {
            'dashboard' => 'เปิด Dashboard',
            'withdraw.manage.index' => 'เปิดบริหารงบประมาณ',
            'withdraw.manage.show' => 'เปิดรายละเอียดงบประมาณ',
            'withdraw.general.index' => 'เปิดรายการเบิกจ่ายทั่วไป',
            'withdraw.project.index' => 'เปิดรายการเบิกจ่ายเงินโครงการ',
            'withdraw.salary.index' => 'เปิดรายการเบิกจ่ายเงินเดือน/ค่าจ้าง',
            'withdraw.approval.index' => 'เปิดหน้าอนุมัติเบิกจ่าย',
            'withdraw.document-review.index' => 'เปิดหน้าตรวจสอบเอกสาร',
            'withdraw.usage-log.index' => 'เปิดบันทึกการใช้งาน',
            'setting.memo-templates' => 'เปิดเทมเพลตบันทึกข้อความ',
            'setting.index' => 'เปิดตั้งค่าข้อมูลพื้นฐาน',
            'withdraw.general.api.save-draft', 'withdraw.salary.api.save-draft' => 'บันทึกแบบร่าง',
            'withdraw.general.api.delete', 'withdraw.salary.api.delete' => 'ลบรายการเบิกจ่าย',
            'withdraw.general.api.list', 'withdraw.salary.api.list' => 'เรียกดูรายการเบิกจ่าย',
            'withdraw.general.api.detail', 'withdraw.salary.api.detail' => 'เปิดรายการเบิกจ่าย',
            'withdraw.general.memo-pdf', 'withdraw.project.memo-pdf', 'withdraw.salary.memo-pdf', 'withdraw.travel.memo-pdf' => 'เปิดไฟล์บันทึกข้อความ',
            'withdraw.general.memo-finance-pdf', 'withdraw.project.memo-finance-pdf' => 'เปิดไฟล์บันทึกข้อความการเงิน',
            'withdraw.general.api.file', 'withdraw.salary.api.file' => 'ดาวน์โหลดไฟล์แนบ',
            'withdraw.project.api.procurement.save' => 'บันทึกเอกสารจัดซื้อจัดจ้าง',
            'withdraw.project.api.procurement.list' => 'เรียกดูเอกสารจัดซื้อจัดจ้าง',
            'withdraw.project.api.procurement.show' => 'เปิดเอกสารจัดซื้อจัดจ้าง',
            'withdraw.project.api.procurement.pdf', 'withdraw.project.api.procurement-pdf' => 'เปิด PDF เอกสารจัดซื้อจัดจ้าง',
            'withdraw.project.api.procurement.file' => 'ดาวน์โหลดไฟล์จัดซื้อจัดจ้าง',
            'withdraw.project.api.handoffs.recreate' => 'สร้างรายการส่งต่อจากโครงการใหม่',
            'withdraw.project.api.handoffs.dismiss' => 'ปิดรายการส่งต่อจากโครงการ',
            'withdraw.travel.api.pending.cancel' => 'ยกเลิกรายการรอเบิกจ่ายเดินทาง',
            'withdraw.salary.api.resend-notification' => 'ส่งการแจ้งเตือนอนุมัติอีกครั้ง',
            'withdraw.salary.api.one-planning-send' => 'ส่งข้อมูลไป One Planning',
            'withdraw.salary.api.one-planning-preview' => 'ดูตัวอย่างข้อมูล One Planning',
            'setting.api.save' => 'บันทึกการตั้งค่า',
            'setting.api.delete' => 'ลบข้อมูลตั้งค่า',
            'setting.api.toggle' => 'เปิดหรือปิดการใช้งานในการตั้งค่า',
            'setting.api.approvers.toggle-notification' => 'เปิดหรือปิดการแจ้งเตือนผู้อนุมัติ',
            'setting.api.memo-templates.save' => 'บันทึกเทมเพลตบันทึกข้อความ',
            'budget.api.manage.save' => 'บันทึกงบประมาณ',
            'budget.api.manage.delete' => 'ลบงบประมาณ',
            'budget.api.manage-list.save' => 'บันทึกรายการงบประมาณ',
            'budget.api.manage-list.delete' => 'ลบรายการงบประมาณ',
            'budget.api.expense.save' => 'บันทึกรายจ่าย',
            'budget.api.expense.delete' => 'ลบรายจ่าย',
            'budget.api.expense-sub.save' => 'บันทึกรายจ่ายย่อย',
            'budget.api.expense-sub.delete' => 'ลบรายจ่ายย่อย',
            'budget.api.manage.finalize-plan' => 'ยืนยันแผนงบประมาณ',
            'handoff.denied' => 'ถูกปฏิเสธการเข้าสู่ระบบ',
            'withdraw.project.from-project' => 'รับรายการส่งต่อจากระบบโครงการ',
            'withdraw.project.api.handoff' => 'รับข้อมูลส่งต่อจากโครงการ',
            'withdraw.project.api.handoffs.pending' => 'เรียกดูรายการส่งต่อที่รออยู่',
            'withdraw.project.api.amount' => 'ดูยอดเงินโครงการ',
            'withdraw.project.api.approve-memo-pdf' => 'เปิด PDF บันทึกข้อความอนุมัติ',
            'withdraw.project.api.print-pdf' => 'เปิด PDF สำหรับพิมพ์',
            'withdraw.project.api.procurement.winners' => 'เรียกดูผู้ชนะจัดซื้อจัดจ้าง',
            'withdraw.general.api.pay-list-options', 'withdraw.salary.api.pay-list-options' => 'เรียกดูรายการจ่าย',
            'withdraw.general.api.document-options', 'withdraw.salary.api.document-options' => 'เรียกดูประเภทเอกสารประกอบ',
            'withdraw.general.api.expense-chart-options', 'withdraw.salary.api.expense-chart-options' => 'เรียกดูผังค่าใช้จ่าย',
            'withdraw.general.api.budget-source-options', 'withdraw.salary.api.budget-source-options' => 'โหลดตัวเลือกงบประมาณในฟอร์มเบิกจ่าย',
            'withdraw.general.api.next-running-no', 'withdraw.salary.api.next-running-no' => 'ขอเลขที่รายการถัดไป',
            'withdraw.general.api.signature-proxy', 'withdraw.salary.api.signature-proxy' => 'โหลดลายเซ็น',
            'withdraw.project.external.withdraws' => 'ระบบภายนอกเรียกดูรายการเบิกจ่ายโครงการ',
            'withdraw.project.external.pending-confirms' => 'ระบบภายนอกเรียกดูรายการรอยืนยัน',
            'withdraw.project.external.memo-pdf' => 'ระบบภายนอกเปิดบันทึกข้อความ',
            'withdraw.project.external.approve-memo-pdf' => 'ระบบภายนอกเปิดบันทึกข้อความอนุมัติ',
            'withdraw.project.external.print-pdf' => 'ระบบภายนอกเปิดไฟล์สำหรับพิมพ์',
            'withdraw.project.external.file' => 'ระบบภายนอกดาวน์โหลดไฟล์แนบ',
            'withdraw.project.external.procurement-pdf' => 'ระบบภายนอกเปิด PDF จัดซื้อจัดจ้าง',
            'withdraw.project.external.procurement-file' => 'ระบบภายนอกดาวน์โหลดไฟล์จัดซื้อจัดจ้าง',
            'budget.api.manage.list' => 'เรียกดูรายการงบประมาณ',
            'budget.api.manage.expenses' => 'เรียกดูรายจ่ายของงบประมาณ',
            'budget.api.expense.subs' => 'เรียกดูรายจ่ายย่อย',
            'budget.api.manage.history' => 'เรียกดูประวัติงบประมาณ',
            'budget.api.cascade.events-by-plan' => 'เรียกดูงานตามแผน',
            'budget.api.cascade.activities-by-event' => 'เรียกดูกิจกรรมตามงาน',
            'withdraw.api.memo-template' => 'เรียกเทมเพลตบันทึกข้อความ',
            'setting.api.options' => 'เรียกดูตัวเลือกตั้งค่า',
            'setting.api.memo-templates.catalog' => 'เรียกดูรายการเทมเพลตบันทึกข้อความ',
            'setting.api.memo-templates.resolve' => 'เรียกเทมเพลตบันทึกข้อความที่ใช้ได้',
            'setting.api.list' => 'เรียกดูข้อมูลตั้งค่า',
            'logout' => 'ออกจากระบบ',
            default => null,
        };
    }
}
