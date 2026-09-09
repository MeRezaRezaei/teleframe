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
        Schema::create('tl_messages_emoji_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_79e504317da9a3734f2c5420');
            $table->index('account_id', 'ix_169129f31f141a1087c4b57e');
        });
        Schema::create('tl_messages_emoji_groups_emoji_groups', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_emoji_groups')->cascadeOnDelete();
            $table->integer('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7d42b5ffa796326eb6105a5c');
        });
        Schema::create('tl_messages_emoji_groups_emoji_groups__groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_emoji_groups_emoji_groups')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d13a59cbbbff4847d2b9');
            $table->index('account_id', 'ix_fe90cc93777b0711f680bef6');
        });
        Schema::create('tl_messages_emoji_groups_emoji_groups_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_emoji_groups')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1bbcf68a914a37ad3d6c974d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_emoji_groups_emoji_groups_not_modified');
        Schema::dropIfExists('tl_messages_emoji_groups_emoji_groups__groups');
        Schema::dropIfExists('tl_messages_emoji_groups_emoji_groups');
        Schema::dropIfExists('tl_messages_emoji_groups');
    }
};
