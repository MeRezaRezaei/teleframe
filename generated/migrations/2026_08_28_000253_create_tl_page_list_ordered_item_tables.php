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
        Schema::create('tl_page_list_ordered_item_page_list_ordered_item_blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('checkbox')->default(false);
            $table->boolean('checked')->default(false);
            $table->text('num')->nullable();
            $table->integer('tl_value')->nullable();
            $table->text('tl_type')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_423445a764d3b2607acbc454');
            $table->index('account_id', 'ix_6d63ec62be61284ef143afd5');
        });
        Schema::create('tl_page_list_ordered_item_page_list_ordered_i_8d9d190d33ee', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_list_ordered_item_page_list_ordered_item_blocks', 'id', 'fk_27eb6986813bd868c94d44a4')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_19a9957c643035cbcbc1');
            $table->index('account_id', 'ix_f4a2fce9cc172bd899202df3');
        });
        Schema::create('tl_page_list_ordered_item_page_list_ordered_item_text', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('checkbox')->default(false);
            $table->boolean('checked')->default(false);
            $table->text('num')->nullable();
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_103b5685431b602845120691');
            $table->integer('tl_value')->nullable();
            $table->text('tl_type')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_94f20ed2ec18aeb4d84a50bf');
            $table->index('account_id', 'ix_c1e02226eec60e589dd9bd01');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_list_ordered_item_page_list_ordered_item_text');
        Schema::dropIfExists('tl_page_list_ordered_item_page_list_ordered_i_8d9d190d33ee');
        Schema::dropIfExists('tl_page_list_ordered_item_page_list_ordered_item_blocks');
    }
};
