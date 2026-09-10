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
        Schema::create('tl_attach_menu_bot_attach_menu_bot', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('inactive')->default(false);
            $table->boolean('has_settings')->default(false);
            $table->boolean('request_write_access')->default(false);
            $table->boolean('show_in_attach_menu')->default(false);
            $table->boolean('show_in_side_menu')->default(false);
            $table->boolean('side_menu_disclaimer_needed')->default(false);
            $table->bigInteger('bot_id')->nullable();
            $table->index('bot_id', 'ix_50ac3ed45c02df1b1ab1727c');
            $table->text('short_name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c111991a58c0dd7091defe11');
            $table->index('account_id', 'ix_97605ab3525af464d850eb99');
        });
        Schema::create('tl_attach_menu_bot_attach_menu_bot__peer_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_attach_menu_bot_attach_menu_bot', 'id', 'fk_5e2b7263d81bea3c8f181323')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_37d5da085ed568f54f45');
            $table->index('account_id', 'ix_5abf7611340b15459da65bb9');
        });
        Schema::create('tl_attach_menu_bot_attach_menu_bot__icons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_attach_menu_bot_attach_menu_bot', 'id', 'fk_b5c3f2e46fc6b6c34cb4113e')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
