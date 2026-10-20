<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdraw_general', function (Blueprint $table) {
            $table->id('withdraw_general_id');
            $table->string('withdraw_general_subject');
            $table->string('withdraw_general_department_code')->nullable();
            $table->string('withdraw_general_document_number')->nullable();
            $table->string('withdraw_general_document_date')->nullable();
            $table->string('withdraw_general_to')->nullable();
            $table->text('withdraw_general_paragraph_1')->nullable();
            $table->text('withdraw_general_paragraph_2')->nullable();
            $table->text('withdraw_general_paragraph_3')->nullable();
            $table->string('withdraw_general_applicant_staff_id')->nullable();
            $table->string('withdraw_general_reviewer_staff_id')->nullable();
            $table->longText('withdraw_general_items_json')->nullable();
            $table->longText('withdraw_general_documents_json')->nullable();
            $table->longText('withdraw_general_uploaded_files_json')->nullable();
            $table->decimal('withdraw_general_total_amount', 15, 2)->default(0);
            $table->string('withdraw_general_status', 30)->default('saved');
            $table->dateTime('withdraw_general_date_create')->nullable();
            $table->dateTime('withdraw_general_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_general');
    }
};
