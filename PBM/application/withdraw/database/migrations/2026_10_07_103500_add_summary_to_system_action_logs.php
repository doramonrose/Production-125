<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_action_logs')) {
            return;
        }

        Schema::table('system_action_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('system_action_logs', 'staff_name')) {
                $table->string('staff_name', 255)->nullable()->after('staff_id');
            }
            if (! Schema::hasColumn('system_action_logs', 'action_summary')) {
                $table->string('action_summary', 500)->nullable()->after('route_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_action_logs')) {
            return;
        }

        Schema::table('system_action_logs', function (Blueprint $table) {
            if (Schema::hasColumn('system_action_logs', 'action_summary')) {
                $table->dropColumn('action_summary');
            }
            if (Schema::hasColumn('system_action_logs', 'staff_name')) {
                $table->dropColumn('staff_name');
            }
        });
    }
};
