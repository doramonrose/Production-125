<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdraw_set_document_type', function (Blueprint $table) {
            $table->id('withdraw_set_document_type_id');
            $table->integer('withdraw_set_document_type_code');
            $table->unique('withdraw_set_document_type_code', 'wsdt_code_uq');
            $table->string('withdraw_set_document_type_name');
            $table->string('withdraw_set_document_type_detail')->nullable();
            $table->boolean('withdraw_set_document_type_status')->default(true);
            $table->dateTime('withdraw_set_document_type_date_create')->nullable();
            $table->dateTime('withdraw_set_document_type_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
        });

        Schema::create('withdraw_set_document_item', function (Blueprint $table) {
            $table->id('withdraw_set_document_item_id');
            $table->unsignedBigInteger('withdraw_set_document_type_code');
            $table->integer('withdraw_set_document_item_code');
            $table->unique('withdraw_set_document_item_code', 'wsdi_code_uq');
            $table->string('withdraw_set_document_item_name');
            $table->string('withdraw_set_document_item_detail')->nullable();
            $table->boolean('withdraw_set_document_item_status')->default(true);
            $table->dateTime('withdraw_set_document_item_date_create')->nullable();
            $table->dateTime('withdraw_set_document_item_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
            $table->index('withdraw_set_document_type_code', 'wsdi_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_set_document_item');
        Schema::dropIfExists('withdraw_set_document_type');
    }
};
