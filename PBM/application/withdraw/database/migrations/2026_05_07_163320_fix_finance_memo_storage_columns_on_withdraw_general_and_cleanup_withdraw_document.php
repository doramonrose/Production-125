<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('withdraw_general')) {
            Schema::table('withdraw_general', function (Blueprint $table) {
                if (! Schema::hasColumn('withdraw_general', 'withdraw_general_finance_document_number')) {
                    $table->string('withdraw_general_finance_document_number')->nullable()->after('withdraw_general_reviewer_staff_id');
                }
                if (! Schema::hasColumn('withdraw_general', 'withdraw_general_finance_document_date')) {
                    $table->string('withdraw_general_finance_document_date')->nullable()->after('withdraw_general_finance_document_number');
                }
                if (! Schema::hasColumn('withdraw_general', 'withdraw_general_finance_subject')) {
                    $table->text('withdraw_general_finance_subject')->nullable()->after('withdraw_general_finance_document_date');
                }
                if (! Schema::hasColumn('withdraw_general', 'withdraw_general_finance_to')) {
                    $table->text('withdraw_general_finance_to')->nullable()->after('withdraw_general_finance_subject');
                }
                if (! Schema::hasColumn('withdraw_general', 'withdraw_general_finance_paragraph_1')) {
                    $table->longText('withdraw_general_finance_paragraph_1')->nullable()->after('withdraw_general_finance_to');
                }
                if (! Schema::hasColumn('withdraw_general', 'withdraw_general_finance_paragraph_2')) {
                    $table->longText('withdraw_general_finance_paragraph_2')->nullable()->after('withdraw_general_finance_paragraph_1');
                }
                if (! Schema::hasColumn('withdraw_general', 'withdraw_general_finance_paragraph_3')) {
                    $table->longText('withdraw_general_finance_paragraph_3')->nullable()->after('withdraw_general_finance_paragraph_2');
                }
            });
        }

        if (Schema::hasTable('withdraw_document')) {
            Schema::table('withdraw_document', function (Blueprint $table) {
                $dropColumns = [];
                foreach (['subject', 'to', 'paragraph1', 'paragraph2', 'paragraph3'] as $column) {
                    if (Schema::hasColumn('withdraw_document', $column)) {
                        $dropColumns[] = $column;
                    }
                }
                if (count($dropColumns) > 0) {
                    $table->dropColumn($dropColumns);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('withdraw_general')) {
            Schema::table('withdraw_general', function (Blueprint $table) {
                $dropColumns = [];
                foreach ([
                    'withdraw_general_finance_document_number',
                    'withdraw_general_finance_document_date',
                    'withdraw_general_finance_subject',
                    'withdraw_general_finance_to',
                    'withdraw_general_finance_paragraph_1',
                    'withdraw_general_finance_paragraph_2',
                    'withdraw_general_finance_paragraph_3',
                ] as $column) {
                    if (Schema::hasColumn('withdraw_general', $column)) {
                        $dropColumns[] = $column;
                    }
                }
                if (count($dropColumns) > 0) {
                    $table->dropColumn($dropColumns);
                }
            });
        }

        if (Schema::hasTable('withdraw_document')) {
            Schema::table('withdraw_document', function (Blueprint $table) {
                if (! Schema::hasColumn('withdraw_document', 'subject')) {
                    $table->text('subject')->nullable()->after('document_date');
                }
                if (! Schema::hasColumn('withdraw_document', 'to')) {
                    $table->text('to')->nullable()->after('subject');
                }
                if (! Schema::hasColumn('withdraw_document', 'paragraph1')) {
                    $table->longText('paragraph1')->nullable()->after('to');
                }
                if (! Schema::hasColumn('withdraw_document', 'paragraph2')) {
                    $table->longText('paragraph2')->nullable()->after('paragraph1');
                }
                if (! Schema::hasColumn('withdraw_document', 'paragraph3')) {
                    $table->longText('paragraph3')->nullable()->after('paragraph2');
                }
            });
        }
    }
};
