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
        Schema::create('tl_messages_chat_invite_join_result_chat_invi_71ed5b26df07', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('updates')->nullable();
            $table->index('updates', 'ix_544b3616f8981b2ba5c14f94');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_288c5583b71a1adaaf114f75');
            $table->index('account_id', 'ix_379bfe77932a177fb8e024c9');
        });
        Schema::create('tl_messages_chat_invite_join_result_chat_invi_dacd8245b982', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('bot_id')->nullable();
            $table->index('bot_id', 'ix_22347be6f45bf427c6098be2');
            $table->bigInteger('webview')->nullable();
            $table->index('webview', 'ix_67c0ccc633e2ed1ad461238d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f48d4b74feb8c7a7b543bb40');
            $table->index('account_id', 'ix_bd81b172549e007cacf4e397');
        });
        Schema::create('tl_messages_chat_invite_join_result_chat_invi_8cf578081a5f', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_9f0d101ec896373685bfac39')->references('id')->on('tl_messages_chat_invite_join_result_chat_invi_dacd8245b982')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_08bba1e8557ab02a0a0a');
            $table->index('account_id', 'ix_d2e5b28056153333fc6f0bf5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_chat_invite_join_result_chat_invi_8cf578081a5f');
        Schema::dropIfExists('tl_messages_chat_invite_join_result_chat_invi_dacd8245b982');
        Schema::dropIfExists('tl_messages_chat_invite_join_result_chat_invi_71ed5b26df07');
    }
};
