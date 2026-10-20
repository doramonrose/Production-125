<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createLookupTables();
        $this->createUnifiedTables();
        $this->assertNoOverlappingParentIds();
        $this->copyLegacyData();
        $this->assertCopiedCounts();
        $this->archiveLegacyTables();
    }

    public function down(): void
    {
        $this->restoreArchivedLegacyTables();

        Schema::dropIfExists('withdraw_action_logs');
        Schema::dropIfExists('withdraw_approval_step');
        Schema::dropIfExists('withdraw_budget');
        Schema::dropIfExists('withdraw_file');
        Schema::dropIfExists('withdraw_req_document');
        Schema::dropIfExists('withdraw_item');
        Schema::dropIfExists('withdraw');
        Schema::dropIfExists('withdraw_status');
        Schema::dropIfExists('withdraw_type');
    }

    private function createLookupTables(): void
    {
        if (! Schema::hasTable('withdraw_type')) {
            Schema::create('withdraw_type', function (Blueprint $table) {
                $table->id('withdraw_type_id');
                $table->string('withdraw_type_code', 50)->unique();
                $table->string('withdraw_type_name', 150);
                $table->integer('withdraw_type_order')->default(0);
                $table->integer('withdraw_type_active')->default(1);
                $table->dateTime('withdraw_type_date_create')->nullable();
                $table->dateTime('withdraw_type_date_modify')->nullable();
                $table->string('staff_create')->nullable();
                $table->string('staff_modify')->nullable();
            });
        }

        $now = now();
        foreach ([
            ['code' => 'general', 'name' => 'เบิกจ่ายทั่วไป', 'order' => 10],
            ['code' => 'salary', 'name' => 'เบิกจ่ายเงินเดือน/ค่าจ้าง', 'order' => 20],
        ] as $row) {
            $existing = DB::table('withdraw_type')->where('withdraw_type_code', $row['code'])->first();
            $payload = [
                'withdraw_type_name' => $row['name'],
                'withdraw_type_order' => $row['order'],
                'withdraw_type_active' => 1,
                'withdraw_type_date_modify' => $now,
            ];
            if ($existing) {
                DB::table('withdraw_type')->where('withdraw_type_id', (int) $existing->withdraw_type_id)->update($payload);
            } else {
                DB::table('withdraw_type')->insert(array_merge($payload, [
                    'withdraw_type_code' => $row['code'],
                    'withdraw_type_date_create' => $now,
                ]));
            }
        }

        if (! Schema::hasTable('withdraw_status')) {
            Schema::create('withdraw_status', function (Blueprint $table) {
                $table->id('withdraw_status_id');
                $table->string('withdraw_status_code', 60)->unique();
                $table->string('withdraw_status_name', 150);
                $table->integer('withdraw_status_order')->default(0);
                $table->integer('withdraw_status_active')->default(1);
                $table->dateTime('withdraw_status_date_create')->nullable();
                $table->dateTime('withdraw_status_date_modify')->nullable();
                $table->string('staff_create')->nullable();
                $table->string('staff_modify')->nullable();
            });
        }

        if (Schema::hasTable('withdraw_general_status')) {
            $legacyStatuses = DB::table('withdraw_general_status')->orderBy('withdraw_general_status_id')->get();
            foreach ($legacyStatuses as $legacy) {
                DB::table('withdraw_status')->updateOrInsert(
                    ['withdraw_status_code' => (string) $legacy->withdraw_general_status_code],
                    [
                        'withdraw_status_name' => (string) $legacy->withdraw_general_status_name,
                        'withdraw_status_order' => (int) $legacy->withdraw_general_status_order,
                        'withdraw_status_active' => (int) $legacy->withdraw_general_status_active,
                        'withdraw_status_date_create' => $legacy->withdraw_general_status_date_create,
                        'withdraw_status_date_modify' => $legacy->withdraw_general_status_date_modify ?? $now,
                        'staff_create' => $legacy->staff_create,
                        'staff_modify' => $legacy->staff_modify,
                    ]
                );
            }
        }

        foreach ([
            ['code' => 'saved', 'name' => 'ร่าง', 'order' => 10],
            ['code' => 'cancelled', 'name' => 'ยกเลิกรายการ', 'order' => 15],
            ['code' => 'pending_primary_review', 'name' => 'รอตรวจสอบ/อนุมัติ', 'order' => 20],
            ['code' => 'pending_additional_review', 'name' => 'รอตรวจสอบ/อนุมัติ', 'order' => 30],
            ['code' => 'pending_finance_review', 'name' => 'รอการเงินตรวจสอบเอกสาร', 'order' => 40],
            ['code' => 'pending_revision', 'name' => 'รอแก้ไข', 'order' => 45],
            ['code' => 'pending_approval', 'name' => 'รออนุมัติ', 'order' => 50],
            ['code' => 'approved', 'name' => 'อนุมัติ', 'order' => 60],
        ] as $row) {
            DB::table('withdraw_status')->updateOrInsert(
                ['withdraw_status_code' => $row['code']],
                [
                    'withdraw_status_name' => $row['name'],
                    'withdraw_status_order' => $row['order'],
                    'withdraw_status_active' => 1,
                    'withdraw_status_date_modify' => $now,
                ]
            );
        }
    }

    private function createUnifiedTables(): void
    {
        if (! Schema::hasTable('withdraw')) {
            Schema::create('withdraw', function (Blueprint $table) {
                $table->id('withdraw_id');
                $table->string('withdraw_type', 50);
                $table->string('withdraw_sub_type', 50)->nullable();
                $table->string('withdraw_subject');
                $table->integer('withdraw_fiscal_year')->nullable();
                $table->string('withdraw_to')->nullable();
                $table->text('withdraw_paragraph_1')->nullable();
                $table->text('withdraw_paragraph_2')->nullable();
                $table->text('withdraw_paragraph_3')->nullable();
                $table->string('withdraw_applicant_staff_id')->nullable();
                $table->string('withdraw_reviewer_staff_id')->nullable();
                $table->string('withdraw_finance_document_date')->nullable();
                $table->text('withdraw_finance_subject')->nullable();
                $table->text('withdraw_finance_to')->nullable();
                $table->longText('withdraw_finance_paragraph_1')->nullable();
                $table->longText('withdraw_finance_paragraph_2')->nullable();
                $table->longText('withdraw_finance_paragraph_3')->nullable();
                $table->decimal('withdraw_total_amount', 15, 2)->default(0);
                $table->unsignedBigInteger('withdraw_budget_manage_id')->nullable();
                $table->string('withdraw_status', 30)->default('saved');
                $table->dateTime('withdraw_date_create')->nullable();
                $table->dateTime('withdraw_date_modify')->nullable();
                $table->string('staff_create')->nullable();
                $table->string('staff_modify')->nullable();

                $table->index('withdraw_type', 'wd_type_idx');
                $table->index('withdraw_sub_type', 'wd_sub_type_idx');
                $table->index('withdraw_status', 'wd_status_idx');
                $table->index(['withdraw_type', 'withdraw_status'], 'wd_type_status_idx');
                $table->index('withdraw_budget_manage_id', 'wd_budget_manage_idx');
            });
        }

        if (! Schema::hasTable('withdraw_item')) {
            Schema::create('withdraw_item', function (Blueprint $table) {
                $table->id('withdraw_item_id');
                $table->unsignedBigInteger('withdraw_id');
                $table->string('withdraw_item_category')->nullable();
                $table->string('withdraw_item_name')->nullable();
                $table->decimal('withdraw_item_amount', 15, 2)->default(0);
                $table->unsignedBigInteger('withdraw_item_expense_chart_id')->nullable();
                $table->unsignedBigInteger('withdraw_item_budget_manage_id')->nullable();
                $table->integer('withdraw_item_order')->default(0);
                $table->dateTime('withdraw_item_date_create')->nullable();
                $table->dateTime('withdraw_item_date_modify')->nullable();
                $table->string('staff_create')->nullable();
                $table->string('staff_modify')->nullable();
                $table->index('withdraw_id', 'wdi_withdraw_idx');
                $table->index('withdraw_item_expense_chart_id', 'wdi_expense_chart_idx');
                $table->index('withdraw_item_budget_manage_id', 'wdi_budget_manage_idx');
            });
        }

        if (! Schema::hasTable('withdraw_req_document')) {
            Schema::create('withdraw_req_document', function (Blueprint $table) {
                $table->id('withdraw_req_document_id');
                $table->unsignedBigInteger('withdraw_id');
                $table->string('withdraw_req_document_type_code')->nullable();
                $table->string('withdraw_req_document_item_code')->nullable();
                $table->string('withdraw_req_document_label')->nullable();
                $table->integer('withdraw_req_document_order')->default(0);
                $table->dateTime('withdraw_req_document_date_create')->nullable();
                $table->dateTime('withdraw_req_document_date_modify')->nullable();
                $table->string('staff_create')->nullable();
                $table->string('staff_modify')->nullable();
                $table->index('withdraw_id', 'wdr_withdraw_idx');
            });
        }

        if (! Schema::hasTable('withdraw_file')) {
            Schema::create('withdraw_file', function (Blueprint $table) {
                $table->id('withdraw_file_id');
                $table->unsignedBigInteger('withdraw_id');
                $table->string('withdraw_file_type_code')->nullable();
                $table->string('withdraw_file_item_code')->nullable();
                $table->string('withdraw_file_path')->nullable();
                $table->string('withdraw_file_name')->nullable();
                $table->bigInteger('withdraw_file_size')->default(0);
                $table->string('withdraw_file_mime')->nullable();
                $table->dateTime('withdraw_file_date_create')->nullable();
                $table->dateTime('withdraw_file_date_modify')->nullable();
                $table->string('staff_create')->nullable();
                $table->string('staff_modify')->nullable();
                $table->index('withdraw_id', 'wdf_withdraw_idx');
            });
        }

        if (! Schema::hasTable('withdraw_approval_step')) {
            Schema::create('withdraw_approval_step', function (Blueprint $table) {
                $table->id('withdraw_approval_step_id');
                $table->unsignedBigInteger('withdraw_id');
                $table->string('withdraw_approval_step_code', 50);
                $table->string('withdraw_approval_step_name', 120);
                $table->integer('withdraw_approval_step_order')->default(0);
                $table->string('withdraw_approval_staff_id')->nullable();
                $table->text('withdraw_approval_custom_status')->nullable();
                $table->string('withdraw_approval_status', 30)->default('pending');
                $table->text('withdraw_approval_comment')->nullable();
                $table->dateTime('withdraw_approval_approved_at')->nullable();
                $table->dateTime('withdraw_approval_notified_at')->nullable();
                $table->dateTime('withdraw_approval_date_create')->nullable();
                $table->dateTime('withdraw_approval_date_modify')->nullable();
                $table->string('staff_create')->nullable();
                $table->string('staff_modify')->nullable();
                $table->index('withdraw_id', 'wda_withdraw_idx');
                $table->index(['withdraw_id', 'withdraw_approval_step_order'], 'wda_withdraw_order_idx');
                $table->index(['withdraw_approval_step_code', 'withdraw_approval_staff_id'], 'wda_code_staff_idx');
            });
        }

        if (! Schema::hasTable('withdraw_action_logs')) {
            Schema::create('withdraw_action_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('withdraw_id')->index();
                $table->string('action_code', 100)->index();
                $table->string('action_label', 255);
                $table->string('from_status', 100)->nullable()->index();
                $table->string('to_status', 100)->nullable()->index();
                $table->string('actor_staff_id', 100)->nullable()->index();
                $table->string('actor_name', 255)->nullable();
                $table->text('comment')->nullable();
                $table->longText('meta_json')->nullable();
                $table->timestamp('action_at')->useCurrent()->index();
            });
        }

        if (! Schema::hasTable('withdraw_budget')) {
            Schema::create('withdraw_budget', function (Blueprint $table) {
                $table->id('withdraw_budget_id');
                $table->unsignedBigInteger('withdraw_id');
                $table->unsignedBigInteger('withdraw_budget_manage_id')->default(0);
                $table->decimal('withdraw_budget_amount', 15, 2)->default(0);
                $table->integer('withdraw_budget_order')->default(0);
                $table->dateTime('withdraw_budget_date_create')->nullable();
                $table->dateTime('withdraw_budget_date_modify')->nullable();
                $table->string('staff_create')->nullable();
                $table->string('staff_modify')->nullable();
                $table->index('withdraw_id', 'wdb_withdraw_idx');
                $table->index('withdraw_budget_manage_id', 'wdb_budget_idx');
            });
        }
    }

    private function copyLegacyData(): void
    {
        if (Schema::hasTable('withdraw_general') && (int) DB::table('withdraw')->where('withdraw_type', 'general')->count() === 0) {
            DB::statement("
                INSERT INTO `withdraw` (
                    `withdraw_id`, `withdraw_type`, `withdraw_sub_type`, `withdraw_subject`, `withdraw_fiscal_year`,
                    `withdraw_to`, `withdraw_paragraph_1`, `withdraw_paragraph_2`, `withdraw_paragraph_3`,
                    `withdraw_applicant_staff_id`, `withdraw_reviewer_staff_id`, `withdraw_finance_document_date`,
                    `withdraw_finance_subject`, `withdraw_finance_to`, `withdraw_finance_paragraph_1`,
                    `withdraw_finance_paragraph_2`, `withdraw_finance_paragraph_3`, `withdraw_total_amount`,
                    `withdraw_budget_manage_id`, `withdraw_status`, `withdraw_date_create`, `withdraw_date_modify`,
                    `staff_create`, `staff_modify`
                )
                SELECT
                    `withdraw_general_id`, 'general', 'default', `withdraw_general_subject`, `withdraw_general_fiscal_year`,
                    `withdraw_general_to`, `withdraw_general_paragraph_1`, `withdraw_general_paragraph_2`, `withdraw_general_paragraph_3`,
                    `withdraw_general_applicant_staff_id`, `withdraw_general_reviewer_staff_id`, `withdraw_general_finance_document_date`,
                    `withdraw_general_finance_subject`, `withdraw_general_finance_to`, `withdraw_general_finance_paragraph_1`,
                    `withdraw_general_finance_paragraph_2`, `withdraw_general_finance_paragraph_3`, `withdraw_general_total_amount`,
                    `withdraw_general_budget_manage_id`, `withdraw_general_status`, `withdraw_general_date_create`, `withdraw_general_date_modify`,
                    `staff_create`, `staff_modify`
                FROM `withdraw_general`
            ");
        }

        if (Schema::hasTable('withdraw_salary') && (int) DB::table('withdraw')->where('withdraw_type', 'salary')->count() === 0) {
            $hasRequestType = Schema::hasColumn('withdraw_salary', 'withdraw_salary_request_type');
            $hasReviewer = Schema::hasColumn('withdraw_salary', 'withdraw_salary_reviewer_staff_id');
            $requestTypeExpr = $hasRequestType
                ? "COALESCE(NULLIF(TRIM(`withdraw_salary_request_type`), ''), 'salary')"
                : "'salary'";
            $reviewerExpr = $hasReviewer ? '`withdraw_salary_reviewer_staff_id`' : 'NULL';

            DB::statement("
                INSERT INTO `withdraw` (
                    `withdraw_id`, `withdraw_type`, `withdraw_sub_type`, `withdraw_subject`, `withdraw_fiscal_year`,
                    `withdraw_to`, `withdraw_paragraph_1`, `withdraw_paragraph_2`, `withdraw_paragraph_3`,
                    `withdraw_applicant_staff_id`, `withdraw_reviewer_staff_id`, `withdraw_finance_document_date`,
                    `withdraw_finance_subject`, `withdraw_finance_to`, `withdraw_finance_paragraph_1`,
                    `withdraw_finance_paragraph_2`, `withdraw_finance_paragraph_3`, `withdraw_total_amount`,
                    `withdraw_budget_manage_id`, `withdraw_status`, `withdraw_date_create`, `withdraw_date_modify`,
                    `staff_create`, `staff_modify`
                )
                SELECT
                    `withdraw_salary_id`, 'salary', {$requestTypeExpr}, `withdraw_salary_subject`, `withdraw_salary_fiscal_year`,
                    `withdraw_salary_to`, `withdraw_salary_paragraph_1`, `withdraw_salary_paragraph_2`, `withdraw_salary_paragraph_3`,
                    `withdraw_salary_applicant_staff_id`, {$reviewerExpr}, `withdraw_salary_finance_document_date`,
                    `withdraw_salary_finance_subject`, `withdraw_salary_finance_to`, `withdraw_salary_finance_paragraph_1`,
                    `withdraw_salary_finance_paragraph_2`, `withdraw_salary_finance_paragraph_3`, `withdraw_salary_total_amount`,
                    `withdraw_salary_budget_manage_id`, `withdraw_salary_status`, `withdraw_salary_date_create`, `withdraw_salary_date_modify`,
                    `staff_create`, `staff_modify`
                FROM `withdraw_salary`
            ");
        }

        $this->copyChildTable('withdraw_general_item', 'withdraw_item', [
            'withdraw_general_id' => 'withdraw_id',
            'withdraw_general_item_category' => 'withdraw_item_category',
            'withdraw_general_item_name' => 'withdraw_item_name',
            'withdraw_general_item_amount' => 'withdraw_item_amount',
            'withdraw_general_item_expense_chart_id' => 'withdraw_item_expense_chart_id',
            'withdraw_general_item_budget_manage_id' => 'withdraw_item_budget_manage_id',
            'withdraw_general_item_order' => 'withdraw_item_order',
            'withdraw_general_item_date_create' => 'withdraw_item_date_create',
            'withdraw_general_item_date_modify' => 'withdraw_item_date_modify',
            'staff_create' => 'staff_create',
            'staff_modify' => 'staff_modify',
        ], 'general');

        $this->copyChildTable('withdraw_salary_item', 'withdraw_item', [
            'withdraw_salary_id' => 'withdraw_id',
            'withdraw_salary_item_category' => 'withdraw_item_category',
            'withdraw_salary_item_name' => 'withdraw_item_name',
            'withdraw_salary_item_amount' => 'withdraw_item_amount',
            'withdraw_salary_item_expense_chart_id' => 'withdraw_item_expense_chart_id',
            'withdraw_salary_item_budget_manage_id' => 'withdraw_item_budget_manage_id',
            'withdraw_salary_item_order' => 'withdraw_item_order',
            'withdraw_salary_item_date_create' => 'withdraw_item_date_create',
            'withdraw_salary_item_date_modify' => 'withdraw_item_date_modify',
            'staff_create' => 'staff_create',
            'staff_modify' => 'staff_modify',
        ], 'salary');

        $this->copyChildTable('withdraw_general_document', 'withdraw_req_document', [
            'withdraw_general_id' => 'withdraw_id',
            'withdraw_general_document_type_code' => 'withdraw_req_document_type_code',
            'withdraw_general_document_item_code' => 'withdraw_req_document_item_code',
            'withdraw_general_document_label' => 'withdraw_req_document_label',
            'withdraw_general_document_order' => 'withdraw_req_document_order',
            'withdraw_general_document_date_create' => 'withdraw_req_document_date_create',
            'withdraw_general_document_date_modify' => 'withdraw_req_document_date_modify',
            'staff_create' => 'staff_create',
            'staff_modify' => 'staff_modify',
        ], 'general');

        $this->copyChildTable('withdraw_salary_document', 'withdraw_req_document', [
            'withdraw_salary_id' => 'withdraw_id',
            'withdraw_salary_document_type_code' => 'withdraw_req_document_type_code',
            'withdraw_salary_document_item_code' => 'withdraw_req_document_item_code',
            'withdraw_salary_document_label' => 'withdraw_req_document_label',
            'withdraw_salary_document_order' => 'withdraw_req_document_order',
            'withdraw_salary_document_date_create' => 'withdraw_req_document_date_create',
            'withdraw_salary_document_date_modify' => 'withdraw_req_document_date_modify',
            'staff_create' => 'staff_create',
            'staff_modify' => 'staff_modify',
        ], 'salary');

        $this->copyChildTable('withdraw_general_file', 'withdraw_file', [
            'withdraw_general_id' => 'withdraw_id',
            'withdraw_general_file_type_code' => 'withdraw_file_type_code',
            'withdraw_general_file_item_code' => 'withdraw_file_item_code',
            'withdraw_general_file_path' => 'withdraw_file_path',
            'withdraw_general_file_name' => 'withdraw_file_name',
            'withdraw_general_file_size' => 'withdraw_file_size',
            'withdraw_general_file_mime' => 'withdraw_file_mime',
            'withdraw_general_file_date_create' => 'withdraw_file_date_create',
            'withdraw_general_file_date_modify' => 'withdraw_file_date_modify',
            'staff_create' => 'staff_create',
            'staff_modify' => 'staff_modify',
        ], 'general');

        $this->copyChildTable('withdraw_salary_file', 'withdraw_file', [
            'withdraw_salary_id' => 'withdraw_id',
            'withdraw_salary_file_type_code' => 'withdraw_file_type_code',
            'withdraw_salary_file_item_code' => 'withdraw_file_item_code',
            'withdraw_salary_file_path' => 'withdraw_file_path',
            'withdraw_salary_file_name' => 'withdraw_file_name',
            'withdraw_salary_file_size' => 'withdraw_file_size',
            'withdraw_salary_file_mime' => 'withdraw_file_mime',
            'withdraw_salary_file_date_create' => 'withdraw_file_date_create',
            'withdraw_salary_file_date_modify' => 'withdraw_file_date_modify',
            'staff_create' => 'staff_create',
            'staff_modify' => 'staff_modify',
        ], 'salary');

        $this->copyChildTable('withdraw_general_approval_step', 'withdraw_approval_step', [
            'withdraw_general_id' => 'withdraw_id',
            'withdraw_general_approval_step_code' => 'withdraw_approval_step_code',
            'withdraw_general_approval_step_name' => 'withdraw_approval_step_name',
            'withdraw_general_approval_step_order' => 'withdraw_approval_step_order',
            'withdraw_general_approval_staff_id' => 'withdraw_approval_staff_id',
            'withdraw_general_approval_custom_status' => 'withdraw_approval_custom_status',
            'withdraw_general_approval_status' => 'withdraw_approval_status',
            'withdraw_general_approval_comment' => 'withdraw_approval_comment',
            'withdraw_general_approval_approved_at' => 'withdraw_approval_approved_at',
            'withdraw_general_approval_notified_at' => 'withdraw_approval_notified_at',
            'withdraw_general_approval_date_create' => 'withdraw_approval_date_create',
            'withdraw_general_approval_date_modify' => 'withdraw_approval_date_modify',
            'staff_create' => 'staff_create',
            'staff_modify' => 'staff_modify',
        ], 'general');

        $this->copyChildTable('withdraw_salary_approval_step', 'withdraw_approval_step', [
            'withdraw_salary_id' => 'withdraw_id',
            'withdraw_salary_approval_step_code' => 'withdraw_approval_step_code',
            'withdraw_salary_approval_step_name' => 'withdraw_approval_step_name',
            'withdraw_salary_approval_step_order' => 'withdraw_approval_step_order',
            'withdraw_salary_approval_staff_id' => 'withdraw_approval_staff_id',
            'withdraw_salary_approval_custom_status' => 'withdraw_approval_custom_status',
            'withdraw_salary_approval_status' => 'withdraw_approval_status',
            'withdraw_salary_approval_comment' => 'withdraw_approval_comment',
            'withdraw_salary_approval_approved_at' => 'withdraw_approval_approved_at',
            'withdraw_salary_approval_notified_at' => 'withdraw_approval_notified_at',
            'withdraw_salary_approval_date_create' => 'withdraw_approval_date_create',
            'withdraw_salary_approval_date_modify' => 'withdraw_approval_date_modify',
            'staff_create' => 'staff_create',
            'staff_modify' => 'staff_modify',
        ], 'salary');

        $this->copyActionLogs('withdraw_general_action_logs', 'withdraw_general_id', 'general');
        $this->copyActionLogs('withdraw_salary_action_logs', 'withdraw_salary_id', 'salary');

        if (Schema::hasTable('withdraw_salary_budget') && (int) DB::table('withdraw_budget')->count() === 0) {
            DB::statement("
                INSERT INTO `withdraw_budget` (
                    `withdraw_id`, `withdraw_budget_manage_id`, `withdraw_budget_amount`, `withdraw_budget_order`,
                    `withdraw_budget_date_create`, `withdraw_budget_date_modify`, `staff_create`, `staff_modify`
                )
                SELECT
                    `withdraw_salary_id`, `withdraw_salary_budget_manage_id`, `withdraw_salary_budget_amount`, `withdraw_salary_budget_order`,
                    `withdraw_salary_budget_date_create`, `withdraw_salary_budget_date_modify`, `staff_create`, `staff_modify`
                FROM `withdraw_salary_budget`
            ");
        }

        $maxId = (int) DB::table('withdraw')->max('withdraw_id');
        if ($maxId > 0) {
            DB::statement('ALTER TABLE `withdraw` AUTO_INCREMENT = '.($maxId + 1));
        }
    }

    /**
     * @param  array<string, string>  $columnMap
     */
    private function copyChildTable(string $source, string $target, array $columnMap, string $type): void
    {
        if (! Schema::hasTable($source) || ! Schema::hasTable($target)) {
            return;
        }

        $already = (int) DB::table($target)
            ->join('withdraw as w', 'w.withdraw_id', '=', $target.'.withdraw_id')
            ->where('w.withdraw_type', $type)
            ->count();
        if ($already > 0) {
            return;
        }

        $available = [];
        foreach (array_keys($columnMap) as $sourceColumn) {
            if (Schema::hasColumn($source, $sourceColumn)) {
                $available[$sourceColumn] = $columnMap[$sourceColumn];
            }
        }
        if ($available === []) {
            return;
        }

        $sourceCols = [];
        $targetCols = [];
        foreach ($available as $from => $to) {
            $sourceCols[] = '`'.$from.'`';
            $targetCols[] = '`'.$to.'`';
        }

        DB::statement(sprintf(
            'INSERT INTO `%s` (%s) SELECT %s FROM `%s`',
            $target,
            implode(', ', $targetCols),
            implode(', ', $sourceCols),
            $source
        ));
    }

    private function copyActionLogs(string $source, string $sourceIdColumn, string $type): void
    {
        if (! Schema::hasTable($source) || ! Schema::hasTable('withdraw_action_logs')) {
            return;
        }

        $already = (int) DB::table('withdraw_action_logs as l')
            ->join('withdraw as w', 'w.withdraw_id', '=', 'l.withdraw_id')
            ->where('w.withdraw_type', $type)
            ->count();
        if ($already > 0) {
            return;
        }

        DB::statement("
            INSERT INTO `withdraw_action_logs` (
                `withdraw_id`, `action_code`, `action_label`, `from_status`, `to_status`,
                `actor_staff_id`, `actor_name`, `comment`, `meta_json`, `action_at`
            )
            SELECT
                `{$sourceIdColumn}`, `action_code`, `action_label`, `from_status`, `to_status`,
                `actor_staff_id`, `actor_name`, `comment`, `meta_json`, `action_at`
            FROM `{$source}`
        ");
    }

    private function assertCopiedCounts(): void
    {
        $checks = [
            'records' => [
                (int) DB::table('withdraw')->count(),
                $this->legacyCount('withdraw_general') + $this->legacyCount('withdraw_salary'),
            ],
            'items' => [
                (int) DB::table('withdraw_item')->count(),
                $this->legacyCount('withdraw_general_item') + $this->legacyCount('withdraw_salary_item'),
            ],
            'req_documents' => [
                (int) DB::table('withdraw_req_document')->count(),
                $this->legacyCount('withdraw_general_document') + $this->legacyCount('withdraw_salary_document'),
            ],
            'files' => [
                (int) DB::table('withdraw_file')->count(),
                $this->legacyCount('withdraw_general_file') + $this->legacyCount('withdraw_salary_file'),
            ],
            'approval_steps' => [
                (int) DB::table('withdraw_approval_step')->count(),
                $this->legacyCount('withdraw_general_approval_step') + $this->legacyCount('withdraw_salary_approval_step'),
            ],
            'action_logs' => [
                (int) DB::table('withdraw_action_logs')->count(),
                $this->legacyCount('withdraw_general_action_logs') + $this->legacyCount('withdraw_salary_action_logs'),
            ],
            'budgets' => [
                (int) DB::table('withdraw_budget')->count(),
                $this->legacyCount('withdraw_salary_budget'),
            ],
        ];

        foreach ($checks as $label => [$actual, $expected]) {
            if ($actual !== $expected) {
                throw new RuntimeException("Withdraw unify copy mismatch for {$label}: copied {$actual}, expected {$expected}.");
            }
        }

    }

    private function assertNoOverlappingParentIds(): void
    {
        if (! Schema::hasTable('withdraw_general') || ! Schema::hasTable('withdraw_salary')) {
            return;
        }

        $overlappingIds = (int) DB::table('withdraw_general as g')
            ->join('withdraw_salary as s', 's.withdraw_salary_id', '=', 'g.withdraw_general_id')
            ->count();
        if ($overlappingIds > 0) {
            throw new RuntimeException('Cannot keep original IDs: general and salary IDs overlap.');
        }
    }

    /**
     * @return list<string>
     */
    private function legacyTableNames(): array
    {
        return [
            'withdraw_general_action_logs',
            'withdraw_salary_action_logs',
            'withdraw_general_approval_step',
            'withdraw_salary_approval_step',
            'withdraw_salary_budget',
            'withdraw_general_file',
            'withdraw_salary_file',
            'withdraw_general_document',
            'withdraw_salary_document',
            'withdraw_general_item',
            'withdraw_salary_item',
            'withdraw_general',
            'withdraw_salary',
            'withdraw_general_status',
        ];
    }

    private function archiveLegacyTables(): void
    {
        foreach ($this->legacyTableNames() as $table) {
            $archive = 'z_legacy_'.$table;
            if (Schema::hasTable($table) && ! Schema::hasTable($archive)) {
                Schema::rename($table, $archive);
            } elseif (Schema::hasTable($table) && Schema::hasTable($archive)) {
                Schema::drop($table);
            }
        }
    }

    private function restoreArchivedLegacyTables(): void
    {
        foreach ($this->legacyTableNames() as $table) {
            $archive = 'z_legacy_'.$table;
            if (Schema::hasTable($archive) && ! Schema::hasTable($table)) {
                Schema::rename($archive, $table);
            }
        }
    }

    private function legacyCount(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }
};
