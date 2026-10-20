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
            if (! Schema::hasColumn('withdraw', 'withdraw_project_amount')) {
                $after = Schema::hasColumn('withdraw', 'withdraw_project_name')
                    ? 'withdraw_project_name'
                    : (Schema::hasColumn('withdraw', 'withdraw_project_code')
                        ? 'withdraw_project_code'
                        : null);
                $column = $table->decimal('withdraw_project_amount', 15, 2)->nullable();
                if ($after !== null) {
                    $column->after($after);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw') || ! Schema::hasColumn('withdraw', 'withdraw_project_amount')) {
            return;
        }

        Schema::table('withdraw', function (Blueprint $table) {
            $table->dropColumn('withdraw_project_amount');
        });
    }
};
