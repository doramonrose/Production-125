<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_set_memo_template')) {
            return;
        }

        if (Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_process_actors_enabled')) {
            return;
        }

        Schema::table('withdraw_set_memo_template', function (Blueprint $table) {
            $table->boolean('withdraw_set_memo_template_process_actors_enabled')
                ->default(false)
                ->after('withdraw_set_memo_template_process_actors');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_set_memo_template')) {
            return;
        }

        if (Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_process_actors_enabled')) {
            Schema::table('withdraw_set_memo_template', function (Blueprint $table) {
                $table->dropColumn('withdraw_set_memo_template_process_actors_enabled');
            });
        }
    }
};
