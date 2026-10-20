<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_document')) {
            return;
        }

        Schema::create('withdraw_document', function (Blueprint $table) {
            $table->id('withdraw_document_id');
            $table->string('withdraw_document_type', 50);
            $table->unsignedBigInteger('withdraw_id');
            $table->string('department_code')->nullable();
            $table->string('document_number')->nullable();
            $table->string('document_date')->nullable();
            $table->integer('running_year');
            $table->integer('running_no');
            $table->timestamps();

            $table->unique(['withdraw_document_type', 'withdraw_id'], 'withdraw_document_type_withdraw_id_unique');
            $table->index(['withdraw_document_type', 'department_code', 'running_year'], 'withdraw_document_type_dep_year_idx');
            $table->index(['withdraw_document_type', 'department_code', 'running_year', 'running_no'], 'withdraw_document_type_dep_year_no_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_document');
    }
};
