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
        Schema::create('tl_messages_web_page_web_page', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('webpage')->nullable();
            $table->index('webpage', 'ix_5ac75bc3b2b7211a762fa050');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5ca44fa608206b71bedf27c8');
            $table->index('account_id', 'ix_7a3e7b311ec4fed8c54e2c2b');
        });
        Schema::create('tl_messages_web_page_web_page__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_web_page_web_page', 'id', 'fk_c5036728e99be901535603ef')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_dc56232ba2a2bcb31f34');
            $table->index('account_id', 'ix_d45fef24716324007d3f631a');
        });
        Schema::create('tl_messages_web_page_web_page__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_web_page_web_page', 'id', 'fk_28e599ef6e3774a0119ba6f5')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8f04e5d134e48f52471e');
            $table->index('account_id', 'ix_2aaa9cdedfa9153dc445e929');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_web_page_web_page__users');
        Schema::dropIfExists('tl_messages_web_page_web_page__chats');
        Schema::dropIfExists('tl_messages_web_page_web_page');
    }
};
