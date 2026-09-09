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
            $table->index('constructor_id', 'ix_cfcfada9993eebe4eead1453');
            $table->index('account_id', 'ix_60731a014c71728b441f526a');
        });
        Schema::create('tl_messages_web_page_web_page', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_web_page')->cascadeOnDelete();
            $table->uuid('webpage');
            $table->index('webpage', 'ix_5ac75bc3b2b7211a762fa050');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7a3e7b311ec4fed8c54e2c2b');
        });
        Schema::create('tl_messages_web_page_web_page__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_web_page_web_page')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_dc56232ba2a2bcb31f34');
            $table->index('account_id', 'ix_d45fef24716324007d3f631a');
        });
        Schema::create('tl_messages_web_page_web_page__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_web_page_web_page')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_messages_web_page');
    }
};
