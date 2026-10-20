<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_travel_handoff')) {
            return;
        }

        Schema::create('withdraw_travel_handoff', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->string('leaves_code', 32)->nullable()->index();
            $table->string('leaves_code_group', 32)->nullable();
            $table->string('subject', 500)->nullable();
            $table->string('applicant_staff_id', 64)->nullable();
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('amount', 15, 2)->nullable();
            $table->unsignedBigInteger('withdraw_id')->nullable();
            $table->longText('payload_json');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_travel_handoff');
    }
};
