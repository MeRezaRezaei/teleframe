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
            $table->index('constructor_id', 'ix_8e5633fdee4c63f17879bdba');
            $table->index('account_id', 'ix_3fd4e46970cb6bc39dd979eb');
        });
        Schema::create('tl_page_list_item_page_list_item_blocks', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_list_item')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('checkbox')->default(false);
            $table->boolean('checked')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_723f9bbcd644839771722f7f');
        });
        Schema::create('tl_page_list_item_page_list_item_blocks__blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_page_list_item_page_list_item_blocks')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1742d0cfc53dfef16e5d');
            $table->index('account_id', 'ix_860b8464d27a36eabb82ae45');
        });
        Schema::create('tl_page_list_item_page_list_item_text', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_list_item')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('checkbox')->default(false);
            $table->boolean('checked')->default(false);
            $table->uuid('text');
            $table->index('text', 'ix_2f7bbac5fa162ac38d746145');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2110c10c38d67d23319922c3');
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
