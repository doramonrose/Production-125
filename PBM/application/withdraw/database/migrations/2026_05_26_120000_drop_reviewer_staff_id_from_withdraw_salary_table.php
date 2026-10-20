<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_salary')) {
            return;
        }

        Schema::table('withdraw_salary', function (Blueprint $table) {
            if (Schema::hasColumn('withdraw_salary', 'withdraw_salary_reviewer_staff_id')) {
                $table->dropColumn('withdraw_salary_reviewer_staff_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_salary')) {
            return;
        }

        Schema::table('withdraw_salary', function (Blueprint $table) {
            if (! Schema::hasColumn('withdraw_salary', 'withdraw_salary_reviewer_staff_id')) {
                $table->string('withdraw_salary_reviewer_staff_id')->nullable()->after('withdraw_salary_applicant_staff_id');
            }
        });
    }
};
