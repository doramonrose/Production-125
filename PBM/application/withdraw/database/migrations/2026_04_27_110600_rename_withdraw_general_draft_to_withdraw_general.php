<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_general_draft') && ! Schema::hasTable('withdraw_general')) {
            Schema::rename('withdraw_general_draft', 'withdraw_general');
        }

        if (! Schema::hasTable('withdraw_general')) {
            return;
        }

        $renameColumnIfExists = static function (string $from, string $to): void {
            if (Schema::hasColumn('withdraw_general', $from) && ! Schema::hasColumn('withdraw_general', $to)) {
                Schema::table('withdraw_general', function (Blueprint $table) use ($from, $to) {
                    $table->renameColumn($from, $to);
                });
            }
        };

        $renameColumnIfExists('withdraw_general_draft_id', 'withdraw_general_id');
        $renameColumnIfExists('withdraw_general_draft_subject', 'withdraw_general_subject');
        $renameColumnIfExists('withdraw_general_draft_department_code', 'withdraw_general_department_code');
        $renameColumnIfExists('withdraw_general_draft_document_number', 'withdraw_general_document_number');
        $renameColumnIfExists('withdraw_general_draft_document_date', 'withdraw_general_document_date');
        $renameColumnIfExists('withdraw_general_draft_to', 'withdraw_general_to');
        $renameColumnIfExists('withdraw_general_draft_paragraph_1', 'withdraw_general_paragraph_1');
        $renameColumnIfExists('withdraw_general_draft_paragraph_2', 'withdraw_general_paragraph_2');
        $renameColumnIfExists('withdraw_general_draft_paragraph_3', 'withdraw_general_paragraph_3');
        $renameColumnIfExists('withdraw_general_draft_applicant_staff_id', 'withdraw_general_applicant_staff_id');
        $renameColumnIfExists('withdraw_general_draft_reviewer_staff_id', 'withdraw_general_reviewer_staff_id');
        $renameColumnIfExists('withdraw_general_draft_items_json', 'withdraw_general_items_json');
        $renameColumnIfExists('withdraw_general_draft_documents_json', 'withdraw_general_documents_json');
        $renameColumnIfExists('withdraw_general_draft_uploaded_files_json', 'withdraw_general_uploaded_files_json');
        $renameColumnIfExists('withdraw_general_draft_total_amount', 'withdraw_general_total_amount');
        $renameColumnIfExists('withdraw_general_draft_status', 'withdraw_general_status');
        $renameColumnIfExists('withdraw_general_draft_date_create', 'withdraw_general_date_create');
        $renameColumnIfExists('withdraw_general_draft_date_modify', 'withdraw_general_date_modify');
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_general')) {
            return;
        }

        $renameColumnIfExists = static function (string $from, string $to): void {
            if (Schema::hasColumn('withdraw_general', $from) && ! Schema::hasColumn('withdraw_general', $to)) {
                Schema::table('withdraw_general', function (Blueprint $table) use ($from, $to) {
                    $table->renameColumn($from, $to);
                });
            }
        };

        $renameColumnIfExists('withdraw_general_id', 'withdraw_general_draft_id');
        $renameColumnIfExists('withdraw_general_subject', 'withdraw_general_draft_subject');
        $renameColumnIfExists('withdraw_general_department_code', 'withdraw_general_draft_department_code');
        $renameColumnIfExists('withdraw_general_document_number', 'withdraw_general_draft_document_number');
        $renameColumnIfExists('withdraw_general_document_date', 'withdraw_general_draft_document_date');
        $renameColumnIfExists('withdraw_general_to', 'withdraw_general_draft_to');
        $renameColumnIfExists('withdraw_general_paragraph_1', 'withdraw_general_draft_paragraph_1');
        $renameColumnIfExists('withdraw_general_paragraph_2', 'withdraw_general_draft_paragraph_2');
        $renameColumnIfExists('withdraw_general_paragraph_3', 'withdraw_general_draft_paragraph_3');
        $renameColumnIfExists('withdraw_general_applicant_staff_id', 'withdraw_general_draft_applicant_staff_id');
        $renameColumnIfExists('withdraw_general_reviewer_staff_id', 'withdraw_general_draft_reviewer_staff_id');
        $renameColumnIfExists('withdraw_general_items_json', 'withdraw_general_draft_items_json');
        $renameColumnIfExists('withdraw_general_documents_json', 'withdraw_general_draft_documents_json');
        $renameColumnIfExists('withdraw_general_uploaded_files_json', 'withdraw_general_draft_uploaded_files_json');
        $renameColumnIfExists('withdraw_general_total_amount', 'withdraw_general_draft_total_amount');
        $renameColumnIfExists('withdraw_general_status', 'withdraw_general_draft_status');
        $renameColumnIfExists('withdraw_general_date_create', 'withdraw_general_draft_date_create');
        $renameColumnIfExists('withdraw_general_date_modify', 'withdraw_general_draft_date_modify');

        if (Schema::hasTable('withdraw_general') && ! Schema::hasTable('withdraw_general_draft')) {
            Schema::rename('withdraw_general', 'withdraw_general_draft');
        }
    }
};
