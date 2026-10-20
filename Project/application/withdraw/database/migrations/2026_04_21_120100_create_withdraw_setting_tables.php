<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('withdraw_set_expenses');
        Schema::dropIfExists('withdraw_set_pay_list');
        Schema::dropIfExists('withdraw_set_category_pay');
        Schema::dropIfExists('withdraw_set_category_budget');
        Schema::dropIfExists('withdraw_set_type');
        Schema::dropIfExists('withdraw_set_source');
        Schema::dropIfExists('withdraw_set_fund');
        Schema::dropIfExists('withdraw_set_activity');
        Schema::dropIfExists('withdraw_set_event');
        Schema::dropIfExists('withdraw_set_plan');
        Schema::dropIfExists('withdraw_set_year');

        Schema::create('withdraw_set_year', function (Blueprint $table) {
            $table->id('withdraw_year_id');
            $table->integer('withdraw_year_code');
            $table->unique('withdraw_year_code', 'wsy_code_uq');
            $table->string('withdraw_year_name');
            $table->string('withdraw_year_detail')->nullable();
            $table->boolean('withdraw_year_status')->default(true);
            $table->dateTime('withdraw_year_date_create')->nullable();
            $table->dateTime('withdraw_year_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
        });

        Schema::create('withdraw_set_plan', function (Blueprint $table) {
            $table->id('withdraw_plan_id');
            $table->integer('withdraw_plan_code');
            $table->unique('withdraw_plan_code', 'wsp_code_uq');
            $table->string('withdraw_plan_name');
            $table->string('withdraw_plan_detail')->nullable();
            $table->boolean('withdraw_plan_status')->default(true);
            $table->dateTime('withdraw_plan_date_create')->nullable();
            $table->dateTime('withdraw_plan_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
        });

        Schema::create('withdraw_set_event', function (Blueprint $table) {
            $table->id('withdraw_event_id');
            $table->unsignedBigInteger('withdraw_plan_code');
            $table->integer('withdraw_event_code');
            $table->unique('withdraw_event_code', 'wse_code_uq');
            $table->string('withdraw_event_name');
            $table->string('withdraw_event_detail')->nullable();
            $table->boolean('withdraw_event_status')->default(true);
            $table->dateTime('withdraw_event_date_create')->nullable();
            $table->dateTime('withdraw_event_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
            $table->index('withdraw_plan_code');
        });

        Schema::create('withdraw_set_activity', function (Blueprint $table) {
            $table->id('withdraw_activity_id');
            $table->unsignedBigInteger('withdraw_event_code');
            $table->integer('withdraw_activity_code');
            $table->unique('withdraw_activity_code', 'wsa_code_uq');
            $table->string('withdraw_activity_name');
            $table->string('withdraw_activity_detail')->nullable();
            $table->boolean('withdraw_activity_status')->default(true);
            $table->dateTime('withdraw_activity_date_create')->nullable();
            $table->dateTime('withdraw_activity_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
            $table->index('withdraw_event_code');
        });

        Schema::create('withdraw_set_fund', function (Blueprint $table) {
            $table->id('withdraw_fund_id');
            $table->integer('withdraw_fund_code');
            $table->unique('withdraw_fund_code', 'wsf_code_uq');
            $table->string('withdraw_fund_name');
            $table->string('withdraw_fund_detail')->nullable();
            $table->boolean('withdraw_fund_status')->default(true);
            $table->dateTime('withdraw_fund_date_create')->nullable();
            $table->dateTime('withdraw_fund_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
        });

        Schema::create('withdraw_set_source', function (Blueprint $table) {
            $table->id('withdraw_source_id');
            $table->integer('withdraw_source_code');
            $table->unique('withdraw_source_code', 'wss_code_uq');
            $table->string('withdraw_source_name');
            $table->string('withdraw_source_detail')->nullable();
            $table->boolean('withdraw_source_status')->default(true);
            $table->dateTime('withdraw_source_date_create')->nullable();
            $table->dateTime('withdraw_source_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
        });

        Schema::create('withdraw_set_type', function (Blueprint $table) {
            $table->id('withdraw_type_id');
            $table->integer('withdraw_type_code');
            $table->unique('withdraw_type_code', 'wst_code_uq');
            $table->string('withdraw_type_name');
            $table->string('withdraw_type_detail')->nullable();
            $table->boolean('withdraw_type_status')->default(true);
            $table->dateTime('withdraw_type_date_create')->nullable();
            $table->dateTime('withdraw_type_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
        });

        Schema::create('withdraw_set_category_budget', function (Blueprint $table) {
            $table->id('withdraw_category_budget_id');
            $table->integer('withdraw_category_budget_code');
            $table->unique('withdraw_category_budget_code', 'wscb_code_uq');
            $table->string('withdraw_category_budget_name');
            $table->string('withdraw_category_budget_detail')->nullable();
            $table->boolean('withdraw_category_budget_status')->default(true);
            $table->dateTime('withdraw_category_budget_date_create')->nullable();
            $table->dateTime('withdraw_category_budget_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
        });

        Schema::create('withdraw_set_category_pay', function (Blueprint $table) {
            $table->id('withdraw_category_pay_id');
            $table->integer('withdraw_category_pay_code');
            $table->unique('withdraw_category_pay_code', 'wscp_code_uq');
            $table->string('withdraw_category_pay_name');
            $table->string('withdraw_category_pay_detail')->nullable();
            $table->boolean('withdraw_category_pay_status')->default(true);
            $table->dateTime('withdraw_category_pay_date_create')->nullable();
            $table->dateTime('withdraw_category_pay_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
        });

        Schema::create('withdraw_set_pay_list', function (Blueprint $table) {
            $table->id('withdraw_pay_id');
            $table->unsignedBigInteger('withdraw_category_pay_code');
            $table->integer('withdraw_pay_code');
            $table->unique('withdraw_pay_code', 'wspl_code_uq');
            $table->string('withdraw_pay_name');
            $table->string('withdraw_pay_detail')->nullable();
            $table->boolean('withdraw_pay_status')->default(true);
            $table->dateTime('withdraw_pay_date_create')->nullable();
            $table->dateTime('withdraw_pay_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
            $table->index('withdraw_category_pay_code');
        });

        Schema::create('withdraw_set_expenses', function (Blueprint $table) {
            $table->id('withdraw_set_expenses_id');
            $table->unsignedBigInteger('withdraw_pay_code');
            $table->integer('withdraw_set_expenses_code');
            $table->unique('withdraw_set_expenses_code', 'wsex_code_uq');
            $table->string('withdraw_set_expenses_name');
            $table->string('withdraw_set_expenses_detail')->nullable();
            $table->boolean('withdraw_set_expenses_status')->default(true);
            $table->dateTime('withdraw_set_expenses_date_create')->nullable();
            $table->dateTime('withdraw_set_expenses_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
            $table->index('withdraw_pay_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_set_expenses');
        Schema::dropIfExists('withdraw_set_pay_list');
        Schema::dropIfExists('withdraw_set_category_pay');
        Schema::dropIfExists('withdraw_set_category_budget');
        Schema::dropIfExists('withdraw_set_type');
        Schema::dropIfExists('withdraw_set_source');
        Schema::dropIfExists('withdraw_set_fund');
        Schema::dropIfExists('withdraw_set_activity');
        Schema::dropIfExists('withdraw_set_event');
        Schema::dropIfExists('withdraw_set_plan');
        Schema::dropIfExists('withdraw_set_year');
    }
};
