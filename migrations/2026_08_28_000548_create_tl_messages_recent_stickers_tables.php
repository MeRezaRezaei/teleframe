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
        Schema::create('tl_messages_recent_stickers_recent_stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_308b2054a333e112495b72ce');
            $table->index('account_id', 'ix_b6f371d804dd570b41caf7a8');
        });
        Schema::create('tl_messages_recent_stickers_recent_stickers__packs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_68a7784ba8d24b0fe1420416')->references('id')->on('tl_messages_recent_stickers_recent_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e42a5de48fcd93ba4113');
            $table->index('account_id', 'ix_c4825c8b6838fb7590a33ab6');
        });
        Schema::create('tl_messages_recent_stickers_recent_stickers__stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_a4fa145907f375b36c70b847')->references('id')->on('tl_messages_recent_stickers_recent_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8bf2f2aab6995796e02d');
            $table->index('account_id', 'ix_7e2dd1ffbd41c9c4fa8df912');
        });
        Schema::create('tl_messages_recent_stickers_recent_stickers__dates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_e650e2742cb09890e4a47912')->references('id')->on('tl_messages_recent_stickers_recent_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_64adcee02e79ae697dee');
            $table->index('account_id', 'ix_7e9c2cb200abcf76e70a743d');
        });
        Schema::create('tl_messages_recent_stickers_recent_stickers_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_13f3acd0c019b09d8906b2cc');
            $table->index('account_id', 'ix_b95d186b8536f7a0855b8c98');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_recent_stickers_recent_stickers_not_modified');
        Schema::dropIfExists('tl_messages_recent_stickers_recent_stickers__dates');
        Schema::dropIfExists('tl_messages_recent_stickers_recent_stickers__stickers');
        Schema::dropIfExists('tl_messages_recent_stickers_recent_stickers__packs');
        Schema::dropIfExists('tl_messages_recent_stickers_recent_stickers');
    }
};
