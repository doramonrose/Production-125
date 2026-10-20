<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_set_finance_reviewer')) {
            return;
        }

        Schema::create('withdraw_set_finance_reviewer', function (Blueprint $table) {
            $table->bigIncrements('withdraw_set_finance_reviewer_id');
            $table->unsignedBigInteger('withdraw_set_finance_reviewer_code')->default(0);
            $table->string('withdraw_set_finance_reviewer_staff_id', 64);
            $table->string('withdraw_set_finance_reviewer_name', 255)->nullable();
            $table->string('withdraw_set_finance_reviewer_detail', 255)->nullable();
            $table->tinyInteger('withdraw_set_finance_reviewer_status')->default(1);
            $table->dateTime('withdraw_set_finance_reviewer_date_create')->nullable();
            $table->dateTime('withdraw_set_finance_reviewer_date_modify')->nullable();
            $table->string('staff_create', 64)->nullable();
            $table->string('staff_modify', 64)->nullable();

            $table->index('withdraw_set_finance_reviewer_staff_id', 'idx_withdraw_set_finance_reviewer_staff_id');
            $table->index('withdraw_set_finance_reviewer_status', 'idx_withdraw_set_finance_reviewer_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_set_finance_reviewer');
    }
};
