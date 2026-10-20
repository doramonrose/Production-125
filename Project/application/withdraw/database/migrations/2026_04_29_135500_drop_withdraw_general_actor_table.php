<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('withdraw_general_actor');
    }

    public function down(): void
    {
        if (Schema::hasTable('withdraw_general_actor')) {
            return;
        }

        Schema::create('withdraw_general_actor', function (Blueprint $table) {
            $table->id('withdraw_general_actor_id');
            $table->unsignedBigInteger('withdraw_general_id');
            $table->string('withdraw_general_actor_staff_id')->nullable();
            $table->string('withdraw_general_actor_status')->nullable();
            $table->integer('withdraw_general_actor_order')->default(0);
            $table->dateTime('withdraw_general_actor_date_create')->nullable();
            $table->dateTime('withdraw_general_actor_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();

            $table->index(['withdraw_general_id', 'withdraw_general_actor_order'], 'wga_general_order_idx');
        });
    }
};

