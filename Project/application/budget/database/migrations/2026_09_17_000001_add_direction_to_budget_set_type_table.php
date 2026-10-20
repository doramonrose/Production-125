<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('budget_set_type') || Schema::hasColumn('budget_set_type', 'budget_type_direction')) {
            return;
        }

        Schema::table('budget_set_type', function (Blueprint $table) {
            $table->char('budget_type_direction', 1)->default('+')->after('budget_type_status');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('budget_set_type') && Schema::hasColumn('budget_set_type', 'budget_type_direction')) {
            Schema::table('budget_set_type', function (Blueprint $table) {
                $table->dropColumn('budget_type_direction');
            });
        }
    }
};