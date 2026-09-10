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
        Schema::create('tl_help_peer_color_option_peer_color_option', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('hidden')->default(false);
            $table->integer('color_id')->nullable();
            $table->bigInteger('colors')->nullable();
            $table->index('colors', 'ix_cb0c1b8c26480e15157bd4f4');
            $table->bigInteger('dark_colors')->nullable();
            $table->index('dark_colors', 'ix_a9cf114398fc37801741a3e4');
            $table->integer('channel_min_level')->nullable();
            $table->integer('group_min_level')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c7f6dd054f94ab859fa5384e');
            $table->index('account_id', 'ix_5cb6b1d76b9be4fa77b0d31d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_peer_color_option_peer_color_option');
    }
};
