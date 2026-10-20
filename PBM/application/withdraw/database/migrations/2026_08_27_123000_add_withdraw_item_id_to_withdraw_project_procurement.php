<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_project_procurement')) {
            return;
        }
        if (Schema::hasColumn('withdraw_project_procurement', 'withdraw_item_id')) {
            return;
        }

        Schema::table('withdraw_project_procurement', function (Blueprint $table) {
            $table->unsignedBigInteger('withdraw_item_id')->nullable()->after('withdraw_id');
            $table->index('withdraw_item_id', 'wpp_item_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_project_procurement')) {
            return;
        }
        if (! Schema::hasColumn('withdraw_project_procurement', 'withdraw_item_id')) {
            return;
        }

        Schema::table('withdraw_project_procurement', function (Blueprint $table) {
            $table->dropIndex('wpp_item_idx');
            $table->dropColumn('withdraw_item_id');
        });
    }
};
