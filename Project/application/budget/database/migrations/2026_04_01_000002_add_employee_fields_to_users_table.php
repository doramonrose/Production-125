<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_id', 100)->nullable()->unique()->after('id');
            $table->string('name_th')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_th');
            $table->string('staff_code', 50)->nullable()->after('name_en');
            $table->string('position')->nullable()->after('staff_code');
            $table->string('department')->nullable()->after('position');
            $table->string('image_url')->nullable()->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'user_id',
                'name_th',
                'name_en',
                'staff_code',
                'position',
                'department',
                'image_url',
            ]);
        });
    }
};
