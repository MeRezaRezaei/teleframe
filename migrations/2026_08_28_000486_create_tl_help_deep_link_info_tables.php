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
        Schema::create('tl_help_deep_link_info_deep_link_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('update_app')->default(false);
            $table->text('message')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_74d95af6659dd67843fe6a6c');
            $table->index('account_id', 'ix_43f58bb00af4a4801b193ae5');
        });
        Schema::create('tl_help_deep_link_info_deep_link_info__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_a5ea6040a4d0b0969137358f')->references('id')->on('tl_help_deep_link_info_deep_link_info')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8fc99dc4c656d4eb5fbf');
            $table->index('account_id', 'ix_1fb823ccc8b197469b7b689d');
        });
        Schema::create('tl_help_deep_link_info_deep_link_info_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ec92243aafc87f5fcb07369b');
            $table->index('account_id', 'ix_7c2db6b8e24dacfa52dfb552');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_deep_link_info_deep_link_info_empty');
        Schema::dropIfExists('tl_help_deep_link_info_deep_link_info__entities');
        Schema::dropIfExists('tl_help_deep_link_info_deep_link_info');
    }
};
