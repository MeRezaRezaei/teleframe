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
        Schema::create('tl_messages_sticker_set_sticker_set', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('set')->nullable();
            $table->index('set', 'ix_b4673a1d1784454beb5420ca');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f157ef7335eac99fe90523b6');
            $table->index('account_id', 'ix_29ec67b8f28893881d8666f9');
        });
        Schema::create('tl_messages_sticker_set_sticker_set__packs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_fb6779ae41227ace0613c7f0')->references('id')->on('tl_messages_sticker_set_sticker_set')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_24aeb8eeb505274d470f');
            $table->index('account_id', 'ix_47921fba72ca3d5b974ceaba');
        });
        Schema::create('tl_messages_sticker_set_sticker_set__keywords', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_7a47acf8bf0ea7e89943d4d7')->references('id')->on('tl_messages_sticker_set_sticker_set')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_134cb618c6e1608a19ba');
            $table->index('account_id', 'ix_d9f302f6d73184e002b703fb');
        });
        Schema::create('tl_messages_sticker_set_sticker_set__documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_d8c78a19db2569ec88908252')->references('id')->on('tl_messages_sticker_set_sticker_set')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0dfb108aeaac4b3f9844');
            $table->index('account_id', 'ix_0367f9a474708e6a442e605d');
        });
        Schema::create('tl_messages_sticker_set_sticker_set_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8eba70ba31398fe717e51799');
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
    }
};
