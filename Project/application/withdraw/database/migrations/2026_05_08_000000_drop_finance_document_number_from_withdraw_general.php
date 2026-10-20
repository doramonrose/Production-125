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

        if (! Schema::hasColumn('withdraw_general', 'withdraw_general_finance_document_number')) {
            return;
        }

        Schema::table('withdraw_general', function (Blueprint $table) {
            $table->dropColumn('withdraw_general_finance_document_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_general')) {
            return;
        }

        if (Schema::hasColumn('withdraw_general', 'withdraw_general_finance_document_number')) {
            return;
        }

        Schema::table('withdraw_general', function (Blueprint $table) {
            $table->string('withdraw_general_finance_document_number')->nullable()->after('withdraw_general_reviewer_staff_id');
        });
    }
};

