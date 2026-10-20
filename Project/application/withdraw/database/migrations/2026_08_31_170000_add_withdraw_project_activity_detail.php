<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw')) {
            return;
        }

        Schema::table('withdraw', function (Blueprint $table) {
            if (! Schema::hasColumn('withdraw', 'withdraw_project_activity_detail')) {
                $after = Schema::hasColumn('withdraw', 'withdraw_project_activity_name')
                    ? 'withdraw_project_activity_name'
                    : (Schema::hasColumn('withdraw', 'withdraw_project_activity_id')
                        ? 'withdraw_project_activity_id'
                        : null);
                $column = $table->text('withdraw_project_activity_detail')->nullable();
                if ($after !== null) {
                    $column->after($after);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw') || ! Schema::hasColumn('withdraw', 'withdraw_project_activity_detail')) {
            return;
        }

        Schema::table('withdraw', function (Blueprint $table) {
            $table->dropColumn('withdraw_project_activity_detail');
        });
    }
};
