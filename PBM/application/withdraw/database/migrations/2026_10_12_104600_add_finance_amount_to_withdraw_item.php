<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_item')) {
            return;
        }

        if (! Schema::hasColumn('withdraw_item', 'withdraw_item_finance_amount')) {
            Schema::table('withdraw_item', function (Blueprint $table) {
                $table->decimal('withdraw_item_finance_amount', 15, 2)->nullable()->after('withdraw_item_amount');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_item') || ! Schema::hasColumn('withdraw_item', 'withdraw_item_finance_amount')) {
            return;
        }

        Schema::table('withdraw_item', function (Blueprint $table) {
            $table->dropColumn('withdraw_item_finance_amount');
        });
    }
};
