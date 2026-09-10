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
        Schema::create('tl_page_list_item_page_list_item_blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('checkbox')->default(false);
            $table->boolean('checked')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_59faf098c09bbd1263c82163');
            $table->index('account_id', 'ix_723f9bbcd644839771722f7f');
        });
        Schema::create('tl_page_list_item_page_list_item_blocks__blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_list_item_page_list_item_blocks', 'id', 'fk_f618f136c9b656f3263df54c')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1742d0cfc53dfef16e5d');
            $table->index('account_id', 'ix_860b8464d27a36eabb82ae45');
        });
        Schema::create('tl_page_list_item_page_list_item_text', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('checkbox')->default(false);
            $table->boolean('checked')->default(false);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_2f7bbac5fa162ac38d746145');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4257243360ca28723b445efe');
            $table->index('account_id', 'ix_2110c10c38d67d23319922c3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_list_item_page_list_item_text');
        Schema::dropIfExists('tl_page_list_item_page_list_item_blocks__blocks');
        Schema::dropIfExists('tl_page_list_item_page_list_item_blocks');
    }
};
