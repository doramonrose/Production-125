<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_general')) {
            return;
        }

        Schema::table('withdraw_general', function (Blueprint $table) {
            $legacyColumns = [
                'withdraw_general_items_json',
                'withdraw_general_documents_json',
                'withdraw_general_uploaded_files_json',
            ];

            foreach ($legacyColumns as $column) {
                if (Schema::hasColumn('withdraw_general', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_general')) {
            return;
        }

        Schema::table('withdraw_general', function (Blueprint $table) {
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_items_json')) {
                $table->longText('withdraw_general_items_json')->nullable()->after('withdraw_general_reviewer_staff_id');
            }
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_documents_json')) {
                $table->longText('withdraw_general_documents_json')->nullable()->after('withdraw_general_items_json');
            }
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_uploaded_files_json')) {
                $table->longText('withdraw_general_uploaded_files_json')->nullable()->after('withdraw_general_documents_json');
            }
        });
    }
};
