<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Intentionally left blank.
        // Finance memo fields must be stored on withdraw_general, not withdraw_document.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank.
    }
};
