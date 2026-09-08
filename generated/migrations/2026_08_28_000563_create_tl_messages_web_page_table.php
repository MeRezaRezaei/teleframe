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
        Schema::create('tl_messages_web_page', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_web_page_web_page', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_web_page')->cascadeOnDelete();
            $table->uuid('webpage');
            $table->timestamps();
        });
        Schema::create('tl_messages_web_page_web_page__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_web_page_web_page')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_dc56232ba2a2bcb31f34');
        });
        Schema::create('tl_messages_web_page_web_page__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_web_page_web_page')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8f04e5d134e48f52471e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_web_page_web_page__users');
        Schema::dropIfExists('tl_messages_web_page_web_page__chats');
        Schema::dropIfExists('tl_messages_web_page_web_page');
        Schema::dropIfExists('tl_messages_web_page');
    }
};
