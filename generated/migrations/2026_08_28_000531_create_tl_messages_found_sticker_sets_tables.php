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
        Schema::create('tl_messages_found_sticker_sets_found_sticker_sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f9e0d250abba683f4915dd99');
            $table->index('account_id', 'ix_0a6ed8b13f2b70b8549834ca');
        });
        Schema::create('tl_messages_found_sticker_sets_found_sticker_sets__sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_d9a2ff6ace264ffaf00ca72b')->references('id')->on('tl_messages_found_sticker_sets_found_sticker_sets')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ba9657045b25c5544ea3');
            $table->index('account_id', 'ix_6bb7fca15e090bc9b9096685');
        });
        Schema::create('tl_messages_found_sticker_sets_found_sticker__68e11d7b41b6', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2062a16bc07a0442b6abda32');
            $table->index('account_id', 'ix_c62d60f367866cc1469c387b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_found_sticker_sets_found_sticker__68e11d7b41b6');
        Schema::dropIfExists('tl_messages_found_sticker_sets_found_sticker_sets__sets');
        Schema::dropIfExists('tl_messages_found_sticker_sets_found_sticker_sets');
    }
};
