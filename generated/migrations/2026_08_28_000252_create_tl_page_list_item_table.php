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
        Schema::create('tl_page_list_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_page_list_item_page_list_item_blocks', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_list_item')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('checkbox')->default(false);
            $table->boolean('checked')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_page_list_item_page_list_item_blocks__blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_list_item_page_list_item_blocks')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1742d0cfc53dfef16e5d');
        });
        Schema::create('tl_page_list_item_page_list_item_text', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_list_item')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('checkbox')->default(false);
            $table->boolean('checked')->default(false);
            $table->uuid('text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_list_item_page_list_item_text');
        Schema::dropIfExists('tl_page_list_item_page_list_item_blocks__blocks');
        Schema::dropIfExists('tl_page_list_item_page_list_item_blocks');
        Schema::dropIfExists('tl_page_list_item');
    }
};
