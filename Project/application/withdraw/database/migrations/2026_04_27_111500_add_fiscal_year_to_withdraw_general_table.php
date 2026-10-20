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
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_fiscal_year')) {
                $table->integer('withdraw_general_fiscal_year')->nullable()->after('withdraw_general_document_date');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_general')) {
            return;
        }

        Schema::table('withdraw_general', function (Blueprint $table) {
            if (Schema::hasColumn('withdraw_general', 'withdraw_general_fiscal_year')) {
                $table->dropColumn('withdraw_general_fiscal_year');
            }
        });
    }
};
