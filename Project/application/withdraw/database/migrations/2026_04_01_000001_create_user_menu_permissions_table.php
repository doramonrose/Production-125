<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 100);
            $table->string('menu_key', 100);
            $table->timestamps();

            $table->unique(['user_id', 'menu_key']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_menu_permissions');
    }
};
