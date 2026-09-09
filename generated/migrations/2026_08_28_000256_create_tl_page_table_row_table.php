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
        Schema::create('tl_page_table_row', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_0c2e7eea4ded7da5cc1c818d');
            $table->index('account_id', 'ix_effa72923e9c04d7f4058daf');
        });
        Schema::create('tl_page_table_row_page_table_row', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_table_row')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_009120f2802304b9ed53379b');
        });
        Schema::create('tl_page_table_row_page_table_row__cells', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_table_row_page_table_row')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4209c2853e8f26993b90');
            $table->index('account_id', 'ix_a0582b5997de26bde12ae034');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_table_row_page_table_row__cells');
        Schema::dropIfExists('tl_page_table_row_page_table_row');
        Schema::dropIfExists('tl_page_table_row');
    }
};
