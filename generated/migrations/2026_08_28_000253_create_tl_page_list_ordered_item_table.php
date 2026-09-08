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
        Schema::create('tl_page_list_ordered_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_page_list_ordered_item_page_list_ordered_item_blocks', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_list_ordered_item')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('checkbox')->default(false);
            $table->boolean('checked')->default(false);
            $table->text('num')->nullable();
            $table->integer('tl_value')->nullable();
            $table->text('tl_type')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_page_list_ordered_item_page_list_ordered_i_8d9d190d33ee', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_list_ordered_item_page_list_ordered_item_blocks')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_19a9957c643035cbcbc1');
        });
        Schema::create('tl_page_list_ordered_item_page_list_ordered_item_text', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_list_ordered_item')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('checkbox')->default(false);
            $table->boolean('checked')->default(false);
            $table->text('num')->nullable();
            $table->uuid('text');
            $table->integer('tl_value')->nullable();
            $table->text('tl_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_list_ordered_item_page_list_ordered_item_text');
        Schema::dropIfExists('tl_page_list_ordered_item_page_list_ordered_i_8d9d190d33ee');
        Schema::dropIfExists('tl_page_list_ordered_item_page_list_ordered_item_blocks');
        Schema::dropIfExists('tl_page_list_ordered_item');
    }
};
