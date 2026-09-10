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
        Schema::create('tl_messages_emoji_groups_emoji_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ff5afd33045e5dfa8d5d2fca');
            $table->index('account_id', 'ix_7d42b5ffa796326eb6105a5c');
        });
        Schema::create('tl_messages_emoji_groups_emoji_groups__groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_bef60b6fcf0030e1a45a1acf')->references('id')->on('tl_messages_emoji_groups_emoji_groups')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d13a59cbbbff4847d2b9');
            $table->index('account_id', 'ix_fe90cc93777b0711f680bef6');
        });
        Schema::create('tl_messages_emoji_groups_emoji_groups_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ed5b0122d2725ee3e42c6bcc');
            $table->index('account_id', 'ix_1bbcf68a914a37ad3d6c974d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_emoji_groups_emoji_groups_not_modified');
        Schema::dropIfExists('tl_messages_emoji_groups_emoji_groups__groups');
        Schema::dropIfExists('tl_messages_emoji_groups_emoji_groups');
    }
};
