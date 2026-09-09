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
        Schema::create('tl_attach_menu_bot_icon', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_569b8d75bcb91c33fd0272e3');
            $table->index('account_id', 'ix_eb5a3355363ce684cdfabe88');
        });
        Schema::create('tl_attach_menu_bot_icon_attach_menu_bot_icon', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_attach_menu_bot_icon')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('name');
            $table->uuid('icon');
            $table->index('icon', 'ix_9b8a90333222e929df44a32c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_af022caec94e9e909404bc51');
        });
        Schema::create('tl_attach_menu_bot_icon_attach_menu_bot_icon__colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_attach_menu_bot_icon_attach_menu_bot_icon')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d6ffc7b81b3e033cbd2c');
            $table->index('account_id', 'ix_2f37ba719811e3947654a5a9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_attach_menu_bot_icon_attach_menu_bot_icon__colors');
        Schema::dropIfExists('tl_attach_menu_bot_icon_attach_menu_bot_icon');
        Schema::dropIfExists('tl_attach_menu_bot_icon');
    }
};
