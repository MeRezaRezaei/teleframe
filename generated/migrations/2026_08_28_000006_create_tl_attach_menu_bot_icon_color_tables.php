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
        Schema::create('tl_attach_menu_bot_icon_color_attach_menu_bot_icon_color', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('name')->nullable();
            $table->integer('color')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_eb092211d120c79acea0e79b');
            $table->index('account_id', 'ix_0bc4cadf559954414d1287ea');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_attach_menu_bot_icon_color_attach_menu_bot_icon_color');
    }
};
