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
        Schema::create('tl_attach_menu_bots_bot', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8379704e39ac2838112ea639');
            $table->index('account_id', 'ix_d2b780df513263d653dde310');
        });
        Schema::create('tl_attach_menu_bots_bot_attach_menu_bots_bot', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_attach_menu_bots_bot')->cascadeOnDelete();
            $table->uuid('bot');
            $table->index('bot', 'ix_5d6166414af4fcf18cc0ff33');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7bc58d73bf1341d384a996d1');
        });
        Schema::create('tl_attach_menu_bots_bot_attach_menu_bots_bot__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_attach_menu_bots_bot_attach_menu_bots_bot')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c1da53dfc8259e0adb8c');
            $table->index('account_id', 'ix_c4857a4fd0219b9ef7599bc2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_attach_menu_bots_bot_attach_menu_bots_bot__users');
        Schema::dropIfExists('tl_attach_menu_bots_bot_attach_menu_bots_bot');
        Schema::dropIfExists('tl_attach_menu_bots_bot');
    }
};
