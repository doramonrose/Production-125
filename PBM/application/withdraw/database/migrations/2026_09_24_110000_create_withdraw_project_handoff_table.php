<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_project_handoff')) {
            return;
        }

        Schema::create('withdraw_project_handoff', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->string('project_id', 100)->nullable()->index();
            $table->string('project_code', 100)->nullable()->index();
            $table->string('project_name', 255)->nullable();
            $table->string('activity_id', 100)->nullable();
            $table->string('activity_name', 255)->nullable();
            $table->string('applicant_staff_id', 100)->nullable()->index();
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('project_amount', 15, 2)->nullable();
            $table->longText('payload_json');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_project_handoff');
    }
};
