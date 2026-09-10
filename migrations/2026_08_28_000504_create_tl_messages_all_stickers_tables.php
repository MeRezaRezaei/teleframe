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
        Schema::create('tl_messages_all_stickers_all_stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7cda4b77d7fb55d252714896');
            $table->index('account_id', 'ix_e989d1c553be3a380d1e192b');
        });
        Schema::create('tl_messages_all_stickers_all_stickers__sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_all_stickers_all_stickers', 'id', 'fk_081ce507a2a0c33951ad3813')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_51ec8a33f928e47883db');
            $table->index('account_id', 'ix_1900add4468467d290a7b012');
        });
        Schema::create('tl_messages_all_stickers_all_stickers_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a8db798eee548cbfb037ece1');
            $table->index('account_id', 'ix_de1f4fda12989e1a3f412af4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_all_stickers_all_stickers_not_modified');
        Schema::dropIfExists('tl_messages_all_stickers_all_stickers__sets');
        Schema::dropIfExists('tl_messages_all_stickers_all_stickers');
    }
};
