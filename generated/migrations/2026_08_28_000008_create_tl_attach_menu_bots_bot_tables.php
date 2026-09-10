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
        Schema::create('tl_attach_menu_bots_bot_attach_menu_bots_bot', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('bot')->nullable();
            $table->index('bot', 'ix_5d6166414af4fcf18cc0ff33');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_77c5e8f5fe6e324188035f5f');
            $table->index('account_id', 'ix_7bc58d73bf1341d384a996d1');
        });
        Schema::create('tl_attach_menu_bots_bot_attach_menu_bots_bot__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_991f146dd5beda7be57596e8')->references('id')->on('tl_attach_menu_bots_bot_attach_menu_bots_bot')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c1da53dfc8259e0adb8c');
            $table->index('account_id', 'ix_c4857a4fd0219b9ef7599bc2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_attach_menu_bots_bot_attach_menu_bots_bot__users');
        Schema::dropIfExists('tl_attach_menu_bots_bot_attach_menu_bots_bot');
    }
};
