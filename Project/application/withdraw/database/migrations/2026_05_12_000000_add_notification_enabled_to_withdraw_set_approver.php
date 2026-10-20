<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_set_approver')) {
            return;
        }
        if (Schema::hasColumn('withdraw_set_approver', 'withdraw_set_approver_notification_enabled')) {
            return;
        }

        Schema::table('withdraw_set_approver', function (Blueprint $table) {
            $table->unsignedTinyInteger('withdraw_set_approver_notification_enabled')
                ->default(1)
                ->after('withdraw_set_approver_status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_set_approver')) {
            return;
        }
        if (! Schema::hasColumn('withdraw_set_approver', 'withdraw_set_approver_notification_enabled')) {
            return;
        }

        Schema::table('withdraw_set_approver', function (Blueprint $table) {
            $table->dropColumn('withdraw_set_approver_notification_enabled');
        });
    }
};
