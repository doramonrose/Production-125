<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_project_procurement')) {
            return;
        }

        Schema::create('withdraw_project_procurement', function (Blueprint $table) {
            $table->id('withdraw_project_procurement_id');
            $table->unsignedBigInteger('withdraw_id');
            $table->string('withdraw_project_procurement_type', 20);
            $table->longText('withdraw_project_procurement_payload');
            $table->dateTime('withdraw_project_procurement_date_create')->nullable();
            $table->dateTime('withdraw_project_procurement_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
            $table->index('withdraw_id', 'wpp_withdraw_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_project_procurement');
    }
};
