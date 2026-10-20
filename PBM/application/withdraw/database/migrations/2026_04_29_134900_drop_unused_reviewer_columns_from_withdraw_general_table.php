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
            if (Schema::hasColumn('withdraw_general', 'withdraw_general_reviewer_comment')) {
                $table->dropColumn('withdraw_general_reviewer_comment');
            }
            if (Schema::hasColumn('withdraw_general', 'withdraw_general_reviewer_approved_by')) {
                $table->dropColumn('withdraw_general_reviewer_approved_by');
            }
            if (Schema::hasColumn('withdraw_general', 'withdraw_general_reviewer_approved_at')) {
                $table->dropColumn('withdraw_general_reviewer_approved_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_general')) {
            return;
        }

        Schema::table('withdraw_general', function (Blueprint $table) {
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_reviewer_approved_at')) {
                $table->dateTime('withdraw_general_reviewer_approved_at')->nullable()->after('withdraw_general_reviewer_staff_id');
            }
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_reviewer_approved_by')) {
                $table->string('withdraw_general_reviewer_approved_by')->nullable()->after('withdraw_general_reviewer_approved_at');
            }
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_reviewer_comment')) {
                $table->text('withdraw_general_reviewer_comment')->nullable()->after('withdraw_general_reviewer_approved_by');
            }
        });
    }
};

