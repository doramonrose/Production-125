<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_general') && ! Schema::hasColumn('withdraw_general', 'withdraw_general_budget_manage_id')) {
            Schema::table('withdraw_general', function (Blueprint $table) {
                $table->unsignedBigInteger('withdraw_general_budget_manage_id')->nullable()->after('withdraw_general_total_amount');
                $table->index('withdraw_general_budget_manage_id', 'wg_budget_manage_idx');
            });
        }

        if (Schema::hasTable('withdraw_general_item')) {
            Schema::table('withdraw_general_item', function (Blueprint $table) {
                if (! Schema::hasColumn('withdraw_general_item', 'withdraw_general_item_expense_chart_id')) {
                    $table->unsignedBigInteger('withdraw_general_item_expense_chart_id')->nullable()->after('withdraw_general_item_amount');
                    $table->index('withdraw_general_item_expense_chart_id', 'wgi_expense_chart_idx');
                }
                if (! Schema::hasColumn('withdraw_general_item', 'withdraw_general_item_budget_manage_id')) {
                    $table->unsignedBigInteger('withdraw_general_item_budget_manage_id')->nullable()->after('withdraw_general_item_expense_chart_id');
                    $table->index('withdraw_general_item_budget_manage_id', 'wgi_budget_manage_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('withdraw_general_item')) {
            Schema::table('withdraw_general_item', function (Blueprint $table) {
                if (Schema::hasColumn('withdraw_general_item', 'withdraw_general_item_budget_manage_id')) {
                    $table->dropIndex('wgi_budget_manage_idx');
                    $table->dropColumn('withdraw_general_item_budget_manage_id');
                }
                if (Schema::hasColumn('withdraw_general_item', 'withdraw_general_item_expense_chart_id')) {
                    $table->dropIndex('wgi_expense_chart_idx');
                    $table->dropColumn('withdraw_general_item_expense_chart_id');
                }
            });
        }

        if (Schema::hasTable('withdraw_general') && Schema::hasColumn('withdraw_general', 'withdraw_general_budget_manage_id')) {
            Schema::table('withdraw_general', function (Blueprint $table) {
                $table->dropIndex('wg_budget_manage_idx');
                $table->dropColumn('withdraw_general_budget_manage_id');
            });
        }
    }
};
