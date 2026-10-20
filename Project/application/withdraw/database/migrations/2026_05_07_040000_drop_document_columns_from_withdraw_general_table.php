<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_general')) {
            return;
        }

        Schema::table('withdraw_general', function (Blueprint $table) {
            $dropColumns = [];
            foreach ([
                'withdraw_general_department_code',
                'withdraw_general_document_number',
                'withdraw_general_document_date',
                'withdraw_general_running_year',
                'withdraw_general_running_no',
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

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_general')) {
            return;
        }

        Schema::table('withdraw_general', function (Blueprint $table) {
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_department_code')) {
                $table->string('withdraw_general_department_code')->nullable()->after('withdraw_general_subject');
            }
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_document_number')) {
                $table->string('withdraw_general_document_number')->nullable()->after('withdraw_general_department_code');
            }
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_document_date')) {
                $table->string('withdraw_general_document_date')->nullable()->after('withdraw_general_document_number');
            }
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_running_year')) {
                $table->integer('withdraw_general_running_year')->nullable()->after('withdraw_general_fiscal_year');
            }
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_running_no')) {
                $table->integer('withdraw_general_running_no')->nullable()->after('withdraw_general_running_year');
            }
        });
    }
};
