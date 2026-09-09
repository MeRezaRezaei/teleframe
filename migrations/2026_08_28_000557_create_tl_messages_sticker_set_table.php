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
        Schema::create('tl_messages_sticker_set', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b0ca8fbe3a0323ada31abcdc');
            $table->index('account_id', 'ix_9f622785250eccc6a892bc6d');
        });
        Schema::create('tl_messages_sticker_set_sticker_set', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_sticker_set')->cascadeOnDelete();
            $table->uuid('set');
            $table->index('set', 'ix_b4673a1d1784454beb5420ca');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_29ec67b8f28893881d8666f9');
        });
        Schema::create('tl_messages_sticker_set_sticker_set__packs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_sticker_set_sticker_set')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_24aeb8eeb505274d470f');
            $table->index('account_id', 'ix_47921fba72ca3d5b974ceaba');
        });
        Schema::create('tl_messages_sticker_set_sticker_set__keywords', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_sticker_set_sticker_set')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_134cb618c6e1608a19ba');
            $table->index('account_id', 'ix_d9f302f6d73184e002b703fb');
        });
        Schema::create('tl_messages_sticker_set_sticker_set__documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_sticker_set_sticker_set')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0dfb108aeaac4b3f9844');
            $table->index('account_id', 'ix_0367f9a474708e6a442e605d');
        });
        Schema::create('tl_messages_sticker_set_sticker_set_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_sticker_set')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f7f8625e60579949b50bf8fe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_sticker_set_sticker_set_not_modified');
        Schema::dropIfExists('tl_messages_sticker_set_sticker_set__documents');
        Schema::dropIfExists('tl_messages_sticker_set_sticker_set__keywords');
        Schema::dropIfExists('tl_messages_sticker_set_sticker_set__packs');
        Schema::dropIfExists('tl_messages_sticker_set_sticker_set');
        Schema::dropIfExists('tl_messages_sticker_set');
    }
};
