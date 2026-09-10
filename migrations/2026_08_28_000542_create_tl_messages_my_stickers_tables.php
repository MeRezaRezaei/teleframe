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
        Schema::create('tl_messages_my_stickers_my_stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_93b4735c5095ac1ef9c4acb1');
            $table->index('account_id', 'ix_722a0aad11340abe4e485662');
        });
        Schema::create('tl_messages_my_stickers_my_stickers__sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_my_stickers_my_stickers', 'id', 'fk_4905ab92aa7d32bdb32e22bf')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d694c9065c8d40ac0ac9');
            $table->index('account_id', 'ix_9443583e368e9855078f92ff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_my_stickers_my_stickers__sets');
        Schema::dropIfExists('tl_messages_my_stickers_my_stickers');
    }
};
