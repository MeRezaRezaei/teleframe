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
        Schema::create('tl_attach_menu_bot', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_500937b8cb5088dec7becb16');
            $table->index('account_id', 'ix_aac9e392879ae3fd8214e2ed');
        });
        Schema::create('tl_attach_menu_bot_attach_menu_bot', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_attach_menu_bot')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('inactive')->default(false);
            $table->boolean('has_settings')->default(false);
            $table->boolean('request_write_access')->default(false);
            $table->boolean('show_in_attach_menu')->default(false);
            $table->boolean('show_in_side_menu')->default(false);
            $table->boolean('side_menu_disclaimer_needed')->default(false);
            $table->bigInteger('bot_id');
            $table->index('bot_id', 'ix_50ac3ed45c02df1b1ab1727c');
            $table->text('short_name');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_97605ab3525af464d850eb99');
        });
        Schema::create('tl_attach_menu_bot_attach_menu_bot__peer_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_attach_menu_bot_attach_menu_bot')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_37d5da085ed568f54f45');
            $table->index('account_id', 'ix_5abf7611340b15459da65bb9');
        });
        Schema::create('tl_attach_menu_bot_attach_menu_bot__icons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_attach_menu_bot_attach_menu_bot')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_00a6b4dd8cc3ba9a0ea5');
            $table->index('account_id', 'ix_2d9502543a8beb6804add713');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_attach_menu_bot_attach_menu_bot__icons');
        Schema::dropIfExists('tl_attach_menu_bot_attach_menu_bot__peer_types');
        Schema::dropIfExists('tl_attach_menu_bot_attach_menu_bot');
        Schema::dropIfExists('tl_attach_menu_bot');
    }
};
