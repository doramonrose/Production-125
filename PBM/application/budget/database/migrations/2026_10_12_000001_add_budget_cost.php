<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('budget_set_cost')) {
            Schema::create('budget_set_cost', function (Blueprint $table) {
                $table->increments('budget_cost_id');
                $table->unsignedInteger('budget_cost_code')->unique();
                $table->string('budget_cost_name');
                $table->string('budget_cost_status', 1)->default('1');
                $table->text('budget_cost_detail')->nullable();
                $table->dateTime('budget_cost_date_create')->nullable();
                $table->dateTime('budget_cost_date_modify')->nullable();
                $table->string('budget_cost_staff_create')->nullable();
                $table->string('budget_cost_staff_modify')->nullable();
            });
        }

        if (Schema::hasTable('budget_manage') && ! Schema::hasColumn('budget_manage', 'budget_cost_code')) {
            Schema::table('budget_manage', function (Blueprint $table) {
                $table->unsignedInteger('budget_cost_code')->default(0)->after('budget_fund_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('budget_manage') && Schema::hasColumn('budget_manage', 'budget_cost_code')) {
            Schema::table('budget_manage', function (Blueprint $table) {
                $table->dropColumn('budget_cost_code');
            });
        }

        Schema::dropIfExists('budget_set_cost');
    }
};
