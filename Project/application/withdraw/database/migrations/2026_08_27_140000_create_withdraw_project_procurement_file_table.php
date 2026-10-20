<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_project_procurement_file')) {
            return;
        }

        Schema::create('withdraw_project_procurement_file', function (Blueprint $table) {
            $table->id('withdraw_project_procurement_file_id');
            $table->unsignedBigInteger('withdraw_project_procurement_id');
            $table->unsignedInteger('withdraw_project_procurement_file_row')->default(0);
            $table->string('withdraw_project_procurement_file_path');
            $table->string('withdraw_project_procurement_file_name')->nullable();
            $table->unsignedBigInteger('withdraw_project_procurement_file_size')->default(0);
            $table->string('withdraw_project_procurement_file_mime', 120)->nullable();
            $table->dateTime('withdraw_project_procurement_file_date_create')->nullable();
            $table->dateTime('withdraw_project_procurement_file_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
            $table->index('withdraw_project_procurement_id', 'wppf_procurement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_project_procurement_file');
    }
};
