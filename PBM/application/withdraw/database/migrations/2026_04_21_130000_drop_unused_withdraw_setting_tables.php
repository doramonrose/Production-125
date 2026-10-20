<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('withdraw_set_pay_list');
        Schema::dropIfExists('withdraw_set_category_pay');
        Schema::dropIfExists('withdraw_set_category_budget');
        Schema::dropIfExists('withdraw_set_type');
        Schema::dropIfExists('withdraw_set_source');
        Schema::dropIfExists('withdraw_set_fund');
        Schema::dropIfExists('withdraw_set_activity');
        Schema::dropIfExists('withdraw_set_event');
        Schema::dropIfExists('withdraw_set_plan');
        Schema::dropIfExists('withdraw_set_year');
    }

    public function down(): void
    {
        // Intentionally left empty.
        // These tables are deprecated for withdraw settings.
    }
};
