<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('budget_set_expenses_withdraw_accounts')) {
            return;
        }

        Schema::create('budget_set_expenses_withdraw_accounts', function (Blueprint $table) {
            $table->id('budget_set_expenses_withdraw_account_id');
            $table->unsignedInteger('budget_set_expenses_code');
            $table->unsignedInteger('withdraw_set_expenses_code');
            $table->string('withdraw_set_expenses_name', 255)->default('');
            $table->dateTime('date_create')->nullable();

            $table->unique(
                ['budget_set_expenses_code', 'withdraw_set_expenses_code'],
                'ux_budget_expense_withdraw_expense'
            );
            $table->index('budget_set_expenses_code', 'idx_budget_set_expenses_code');
            $table->index('withdraw_set_expenses_code', 'idx_withdraw_set_expenses_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_set_expenses_withdraw_accounts');
    }
};
