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
        Schema::create('tl_help_recent_me_urls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_005d55db165d9b32b9fbb7ce');
            $table->index('account_id', 'ix_a3a01c6fe1dada2b864b51c7');
        });
        Schema::create('tl_help_recent_me_urls_recent_me_urls', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_recent_me_urls')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2eda70ab7d40d54ef76769b4');
        });
        Schema::create('tl_help_recent_me_urls_recent_me_urls__urls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_recent_me_urls_recent_me_urls')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4fd7142f344ee8c22d18');
            $table->index('account_id', 'ix_ad58338cce37887120b7c0ee');
        });
        Schema::create('tl_help_recent_me_urls_recent_me_urls__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_recent_me_urls_recent_me_urls')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d4e7488e8a15c9c973ac');
            $table->index('account_id', 'ix_84d992a4b94d0300a3262c16');
        });
        Schema::create('tl_help_recent_me_urls_recent_me_urls__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_recent_me_urls_recent_me_urls')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0a4d3f559d3bbb916b62');
            $table->index('account_id', 'ix_f283f22c8ffe6078ba7fde0f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_recent_me_urls_recent_me_urls__users');
        Schema::dropIfExists('tl_help_recent_me_urls_recent_me_urls__chats');
        Schema::dropIfExists('tl_help_recent_me_urls_recent_me_urls__urls');
        Schema::dropIfExists('tl_help_recent_me_urls_recent_me_urls');
        Schema::dropIfExists('tl_help_recent_me_urls');
    }
};
