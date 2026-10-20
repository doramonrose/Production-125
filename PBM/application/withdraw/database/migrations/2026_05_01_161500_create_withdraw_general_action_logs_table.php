<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdraw_general_action_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('withdraw_general_id')->index();
            $table->string('action_code', 100)->index();
            $table->string('action_label', 255);
            $table->string('from_status', 100)->nullable()->index();
            $table->string('to_status', 100)->nullable()->index();
            $table->string('actor_staff_id', 100)->nullable()->index();
            $table->string('actor_name', 255)->nullable();
            $table->text('comment')->nullable();
            $table->longText('meta_json')->nullable();
            $table->timestamp('action_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_general_action_logs');
    }
};
