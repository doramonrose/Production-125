<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_general')) {
            return;
        }

        Schema::table('withdraw_general', function (Blueprint $table) {
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_running_year')) {
                $table->integer('withdraw_general_running_year')->nullable()->after('withdraw_general_fiscal_year');
            }
            if (! Schema::hasColumn('withdraw_general', 'withdraw_general_running_no')) {
                $table->integer('withdraw_general_running_no')->nullable()->after('withdraw_general_running_year');
            }
        });

        $rows = DB::table('withdraw_general')
            ->orderBy('withdraw_general_id')
            ->get([
                'withdraw_general_id',
                'withdraw_general_document_date',
                'withdraw_general_date_create',
            ]);

        $runningMap = [];
        foreach ($rows as $row) {
            $dateText = trim((string) ($row->withdraw_general_document_date ?? ''));
            if ($dateText === '') {
                $dateText = trim((string) ($row->withdraw_general_date_create ?? ''));
            }

            $date = null;
            try {
                $date = Carbon::parse($dateText);
            } catch (\Throwable $e) {
                $date = now();
            }

            $year = (int) $date->year;
            if ($year >= 2400) {
                $year -= 543;
            }
            $runningMap[$year] = (int) ($runningMap[$year] ?? 0) + 1;

            DB::table('withdraw_general')
                ->where('withdraw_general_id', (int) $row->withdraw_general_id)
                ->update([
                    'withdraw_general_running_year' => $year,
                    'withdraw_general_running_no' => $runningMap[$year],
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_general')) {
            return;
        }

        Schema::table('withdraw_general', function (Blueprint $table) {
            if (Schema::hasColumn('withdraw_general', 'withdraw_general_running_no')) {
                $table->dropColumn('withdraw_general_running_no');
            }
            if (Schema::hasColumn('withdraw_general', 'withdraw_general_running_year')) {
                $table->dropColumn('withdraw_general_running_year');
            }
        });
    }
};
