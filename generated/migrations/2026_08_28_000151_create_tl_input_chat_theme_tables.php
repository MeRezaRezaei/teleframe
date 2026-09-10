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
        Schema::create('tl_input_chat_theme_input_chat_theme', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('emoticon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ea9da6235ffde7f6a16902bc');
            $table->index('account_id', 'ix_6767a18d0988f00482107aed');
        });
        Schema::create('tl_input_chat_theme_input_chat_theme_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_48d9c62e1401ebc906e6e72f');
            $table->index('account_id', 'ix_3adf98acfaaeda4fcd7daeb9');
        });
        Schema::create('tl_input_chat_theme_input_chat_theme_unique_gift', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('slug')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9046a8cdcf342dfd00b9f177');
            $table->index('account_id', 'ix_1a246c916c7d62365e7e9a81');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_chat_theme_input_chat_theme_unique_gift');
        Schema::dropIfExists('tl_input_chat_theme_input_chat_theme_empty');
        Schema::dropIfExists('tl_input_chat_theme_input_chat_theme');
    }
};
