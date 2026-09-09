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
        Schema::create('tl_attach_menu_bot_icon_color', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_f1021b1800d71ad2d6dbce6f');
            $table->index('account_id', 'ix_da22a1b94f4778fba91cccd5');
        });
        Schema::create('tl_attach_menu_bot_icon_color_attach_menu_bot_icon_color', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_attach_menu_bot_icon_color')->cascadeOnDelete();
            $table->text('name');
            $table->integer('color');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0bc4cadf559954414d1287ea');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_attach_menu_bot_icon_color_attach_menu_bot_icon_color');
        Schema::dropIfExists('tl_attach_menu_bot_icon_color');
    }
};
