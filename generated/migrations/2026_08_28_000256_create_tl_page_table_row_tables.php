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
        Schema::create('tl_page_table_row_page_table_row', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f2c7c0c802550e8a909e5711');
            $table->index('account_id', 'ix_009120f2802304b9ed53379b');
        });
        Schema::create('tl_page_table_row_page_table_row__cells', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_table_row_page_table_row', 'id', 'fk_dfb9119b911db63c18359eba')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4209c2853e8f26993b90');
            $table->index('account_id', 'ix_a0582b5997de26bde12ae034');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_table_row_page_table_row__cells');
        Schema::dropIfExists('tl_page_table_row_page_table_row');
    }
};
