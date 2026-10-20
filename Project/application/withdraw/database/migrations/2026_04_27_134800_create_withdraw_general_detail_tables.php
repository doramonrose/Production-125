<?php

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

        Schema::create('withdraw_general_item', function (Blueprint $table) {
            $table->id('withdraw_general_item_id');
            $table->unsignedBigInteger('withdraw_general_id');
            $table->string('withdraw_general_item_category')->nullable();
            $table->string('withdraw_general_item_name')->nullable();
            $table->decimal('withdraw_general_item_amount', 15, 2)->default(0);
            $table->integer('withdraw_general_item_order')->default(0);
            $table->dateTime('withdraw_general_item_date_create')->nullable();
            $table->dateTime('withdraw_general_item_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
            $table->index('withdraw_general_id', 'wgi_general_idx');
        });

        Schema::create('withdraw_general_document', function (Blueprint $table) {
            $table->id('withdraw_general_document_id');
            $table->unsignedBigInteger('withdraw_general_id');
            $table->string('withdraw_general_document_type_code')->nullable();
            $table->string('withdraw_general_document_item_code')->nullable();
            $table->string('withdraw_general_document_label')->nullable();
            $table->integer('withdraw_general_document_order')->default(0);
            $table->dateTime('withdraw_general_document_date_create')->nullable();
            $table->dateTime('withdraw_general_document_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
            $table->index('withdraw_general_id', 'wgd_general_idx');
        });

        Schema::create('withdraw_general_file', function (Blueprint $table) {
            $table->id('withdraw_general_file_id');
            $table->unsignedBigInteger('withdraw_general_id');
            $table->string('withdraw_general_file_type_code')->nullable();
            $table->string('withdraw_general_file_item_code')->nullable();
            $table->string('withdraw_general_file_path')->nullable();
            $table->string('withdraw_general_file_name')->nullable();
            $table->bigInteger('withdraw_general_file_size')->default(0);
            $table->string('withdraw_general_file_mime')->nullable();
            $table->dateTime('withdraw_general_file_date_create')->nullable();
            $table->dateTime('withdraw_general_file_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
            $table->index('withdraw_general_id', 'wgf_general_idx');
        });

        // Backfill existing JSON data to normalized tables
        $rows = DB::table('withdraw_general')->get([
            'withdraw_general_id',
            'withdraw_general_items_json',
            'withdraw_general_documents_json',
            'withdraw_general_uploaded_files_json',
            'withdraw_general_date_create',
            'withdraw_general_date_modify',
            'staff_create',
            'staff_modify',
        ]);

        foreach ($rows as $row) {
            $generalId = (int) ($row->withdraw_general_id ?? 0);
            if ($generalId <= 0) {
                continue;
            }

            $items = json_decode((string) ($row->withdraw_general_items_json ?? '[]'), true);
            if (is_array($items)) {
                $order = 1;
                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    DB::table('withdraw_general_item')->insert([
                        'withdraw_general_id' => $generalId,
                        'withdraw_general_item_category' => trim((string) ($item['category'] ?? '')),
                        'withdraw_general_item_name' => trim((string) ($item['name'] ?? '')),
                        'withdraw_general_item_amount' => (float) ($item['amount'] ?? 0),
                        'withdraw_general_item_order' => $order++,
                        'withdraw_general_item_date_create' => $row->withdraw_general_date_create ?? now(),
                        'withdraw_general_item_date_modify' => $row->withdraw_general_date_modify ?? now(),
                        'staff_create' => (string) ($row->staff_create ?? ''),
                        'staff_modify' => (string) ($row->staff_modify ?? ''),
                    ]);
                }
            }

            $documents = json_decode((string) ($row->withdraw_general_documents_json ?? '[]'), true);
            if (is_array($documents)) {
                $order = 1;
                foreach ($documents as $document) {
                    if (! is_array($document)) {
                        continue;
                    }
                    DB::table('withdraw_general_document')->insert([
                        'withdraw_general_id' => $generalId,
                        'withdraw_general_document_type_code' => trim((string) ($document['type'] ?? '')),
                        'withdraw_general_document_item_code' => trim((string) ($document['item_id'] ?? $document['item'] ?? '')),
                        'withdraw_general_document_label' => trim((string) ($document['label'] ?? '')),
                        'withdraw_general_document_order' => $order++,
                        'withdraw_general_document_date_create' => $row->withdraw_general_date_create ?? now(),
                        'withdraw_general_document_date_modify' => $row->withdraw_general_date_modify ?? now(),
                        'staff_create' => (string) ($row->staff_create ?? ''),
                        'staff_modify' => (string) ($row->staff_modify ?? ''),
                    ]);
                }
            }

            $files = json_decode((string) ($row->withdraw_general_uploaded_files_json ?? '[]'), true);
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (! is_array($file)) {
                        continue;
                    }
                    DB::table('withdraw_general_file')->insert([
                        'withdraw_general_id' => $generalId,
                        'withdraw_general_file_type_code' => trim((string) ($file['type'] ?? '')),
                        'withdraw_general_file_item_code' => trim((string) ($file['item'] ?? '')),
                        'withdraw_general_file_path' => trim((string) ($file['path'] ?? '')),
                        'withdraw_general_file_name' => trim((string) ($file['name'] ?? '')),
                        'withdraw_general_file_size' => (int) ($file['size'] ?? 0),
                        'withdraw_general_file_mime' => trim((string) ($file['mime'] ?? '')),
                        'withdraw_general_file_date_create' => $row->withdraw_general_date_create ?? now(),
                        'withdraw_general_file_date_modify' => $row->withdraw_general_date_modify ?? now(),
                        'staff_create' => (string) ($row->staff_create ?? ''),
                        'staff_modify' => (string) ($row->staff_modify ?? ''),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_general_file');
        Schema::dropIfExists('withdraw_general_document');
        Schema::dropIfExists('withdraw_general_item');
    }
};
