<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tl_page_table_cell', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ff156110b8a1e004816a5302');
            $table->index('account_id', 'ix_40266038e2d4d78e43c4b58b');
        });
        Schema::create('tl_page_table_cell_page_table_cell', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_table_cell')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('header')->default(false);
            $table->boolean('align_center')->default(false);
            $table->boolean('align_right')->default(false);
            $table->boolean('valign_middle')->default(false);
            $table->boolean('valign_bottom')->default(false);
            $table->uuid('text')->nullable();
            $table->index('text', 'ix_34c1f7641b2b260e20e4df91');
            $table->integer('colspan')->nullable();
            $table->integer('rowspan')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8cae48c32e7d89b75253401e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_table_cell_page_table_cell');
        Schema::dropIfExists('tl_page_table_cell');
    }
};
