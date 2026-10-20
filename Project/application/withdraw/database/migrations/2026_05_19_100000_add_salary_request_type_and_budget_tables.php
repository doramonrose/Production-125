<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_salary') && ! Schema::hasColumn('withdraw_salary', 'withdraw_salary_request_type')) {
            Schema::table('withdraw_salary', function (Blueprint $table) {
                $table->string('withdraw_salary_request_type', 50)->default('salary')->after('withdraw_salary_budget_manage_id');
                $table->index('withdraw_salary_request_type', 'ws_request_type_idx');
            });
        }

        if (! Schema::hasTable('withdraw_salary_budget')) {
            Schema::create('withdraw_salary_budget', function (Blueprint $table) {
                $table->id('withdraw_salary_budget_id');
                $table->unsignedBigInteger('withdraw_salary_id');
                $table->unsignedBigInteger('withdraw_salary_budget_manage_id')->default(0);
                $table->decimal('withdraw_salary_budget_amount', 15, 2)->default(0);
                $table->integer('withdraw_salary_budget_order')->default(0);
                $table->dateTime('withdraw_salary_budget_date_create')->nullable();
                $table->dateTime('withdraw_salary_budget_date_modify')->nullable();
                $table->string('staff_create')->nullable();
                $table->string('staff_modify')->nullable();
                $table->index('withdraw_salary_id', 'wsb_salary_idx');
                $table->index('withdraw_salary_budget_manage_id', 'wsb_budget_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_salary_budget');

        if (Schema::hasTable('withdraw_salary') && Schema::hasColumn('withdraw_salary', 'withdraw_salary_request_type')) {
            Schema::table('withdraw_salary', function (Blueprint $table) {
                $table->dropIndex('ws_request_type_idx');
                $table->dropColumn('withdraw_salary_request_type');
            });
        }
    }
};
