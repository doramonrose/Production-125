<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_action_logs') || Schema::hasColumn('system_action_logs', 'listed_items')) {
            return;
        }

        Schema::table('system_action_logs', function (Blueprint $table) {
            $table->text('listed_items')->nullable()->after('action_summary');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_action_logs') || ! Schema::hasColumn('system_action_logs', 'listed_items')) {
            return;
        }

        Schema::table('system_action_logs', function (Blueprint $table) {
            $table->dropColumn('listed_items');
        });
    }
};
