<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_set_book_number')) {
            return;
        }

        Schema::create('withdraw_set_book_number', function (Blueprint $table) {
            $table->bigIncrements('withdraw_set_book_number_id');
            $table->unsignedInteger('withdraw_set_book_number_code')->default(0);
            $table->string('withdraw_set_book_number_type', 255);
            $table->string('withdraw_set_book_number_value', 255);
            $table->tinyInteger('withdraw_set_book_number_status')->default(1);
            $table->string('withdraw_set_book_number_staff_create', 50)->nullable();
            $table->string('withdraw_set_book_number_staff_modify', 50)->nullable();
            $table->dateTime('withdraw_set_book_number_date_create')->nullable();
            $table->dateTime('withdraw_set_book_number_date_modify')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_set_book_number');
    }
};
