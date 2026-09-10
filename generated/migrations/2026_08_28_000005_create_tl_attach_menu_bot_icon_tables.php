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
        Schema::create('tl_attach_menu_bot_icon_attach_menu_bot_icon', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('name')->nullable();
            $table->bigInteger('icon')->nullable();
            $table->index('icon', 'ix_9b8a90333222e929df44a32c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e3adf304d1fd1dde7ea3206b');
            $table->index('account_id', 'ix_af022caec94e9e909404bc51');
        });
        Schema::create('tl_attach_menu_bot_icon_attach_menu_bot_icon__colors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_9c178c84ecdd94cc64601d40')->references('id')->on('tl_attach_menu_bot_icon_attach_menu_bot_icon')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d6ffc7b81b3e033cbd2c');
            $table->index('account_id', 'ix_2f37ba719811e3947654a5a9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_attach_menu_bot_icon_attach_menu_bot_icon__colors');
        Schema::dropIfExists('tl_attach_menu_bot_icon_attach_menu_bot_icon');
    }
};
