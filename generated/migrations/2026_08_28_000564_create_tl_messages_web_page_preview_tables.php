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
        Schema::create('tl_messages_web_page_preview_web_page_preview', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('media')->nullable();
            $table->index('media', 'ix_102e32a65c095babb6518a7c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ed13b1299b648d4c5693d64c');
            $table->index('account_id', 'ix_4bdeaef5c2548f6845d65364');
        });
        Schema::create('tl_messages_web_page_preview_web_page_preview__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_e8bf20d0473d3ef4d70be535')->references('id')->on('tl_messages_web_page_preview_web_page_preview')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6e66ca6ed909bb1e08fe');
            $table->index('account_id', 'ix_b8187f5abafd96b9c0021036');
        });
        Schema::create('tl_messages_web_page_preview_web_page_preview__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_f198dfd1af26fd98f92de4c2')->references('id')->on('tl_messages_web_page_preview_web_page_preview')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_575aa3a64d60c688f78a');
            $table->index('account_id', 'ix_7adfb8a258c0de92a64ade9f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_web_page_preview_web_page_preview__users');
        Schema::dropIfExists('tl_messages_web_page_preview_web_page_preview__chats');
        Schema::dropIfExists('tl_messages_web_page_preview_web_page_preview');
    }
};
