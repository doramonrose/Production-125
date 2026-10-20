<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_action_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->string('request_method', 10);
            $table->string('request_path', 500);
            $table->string('route_name')->nullable()->index();
            $table->string('action_name')->nullable();
            $table->unsignedSmallInteger('http_status');
            $table->unsignedInteger('duration_ms');
            $table->string('staff_id', 100)->nullable()->index();
            $table->string('user_id', 100)->nullable()->index();
            $table->string('system_role', 30)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('query_params')->nullable();
            $table->longText('request_payload')->nullable();
            $table->text('exception_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_action_logs');
    }
};
