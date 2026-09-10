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
        Schema::create('tl_messages_found_stickers_found_stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('next_offset')->nullable();
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_992bbefa527015f6e25273de');
            $table->index('account_id', 'ix_0263b4427186490d390d7ac0');
        });
        Schema::create('tl_messages_found_stickers_found_stickers__stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_4f5f957dae58d5b5b54848e9')->references('id')->on('tl_messages_found_stickers_found_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bfb7cd96b2914cc43a76');
            $table->index('account_id', 'ix_ad71abd0c796a3bac2752907');
        });
        Schema::create('tl_messages_found_stickers_found_stickers_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6bdef332b938c29c34f362f5');
            $table->index('account_id', 'ix_c3602220d8b92202795b33b9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_found_stickers_found_stickers_not_modified');
        Schema::dropIfExists('tl_messages_found_stickers_found_stickers__stickers');
        Schema::dropIfExists('tl_messages_found_stickers_found_stickers');
    }
};
