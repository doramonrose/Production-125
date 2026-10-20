<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_one_planning_send')) {
            return;
        }

        Schema::create('withdraw_one_planning_send', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('withdraw_id')->index();
            $table->string('ref_id', 100)->index();
            $table->string('ref_status_name', 100)->index();
            $table->string('budget_code', 100)->index();
            $table->decimal('budget_value', 15, 2)->default(0);
            $table->text('comment')->nullable();
            $table->string('budget_departments_id', 50)->nullable();
            $table->string('trigger_source', 50)->default('manual')->index();
            $table->boolean('is_success')->default(false)->index();
            $table->string('response_message', 255)->nullable();
            $table->longText('response_json')->nullable();
            $table->string('staff_id', 100)->nullable()->index();
            $table->timestamp('sent_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_one_planning_send');
    }
};
