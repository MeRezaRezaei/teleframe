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
        Schema::create('tl_messages_chat_invite_join_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_972291747ae47341233d36f1');
            $table->index('account_id', 'ix_8a6a04d33d643aef554cfb39');
        });
        Schema::create('tl_messages_chat_invite_join_result_chat_invi_71ed5b26df07', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_chat_invite_join_result')->cascadeOnDelete();
            $table->uuid('updates');
            $table->index('updates', 'ix_544b3616f8981b2ba5c14f94');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_379bfe77932a177fb8e024c9');
        });
        Schema::create('tl_messages_chat_invite_join_result_chat_invi_dacd8245b982', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_chat_invite_join_result')->cascadeOnDelete();
            $table->bigInteger('bot_id');
            $table->index('bot_id', 'ix_22347be6f45bf427c6098be2');
            $table->uuid('webview');
            $table->index('webview', 'ix_67c0ccc633e2ed1ad461238d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bd81b172549e007cacf4e397');
        });
        Schema::create('tl_messages_chat_invite_join_result_chat_invi_8cf578081a5f', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_chat_invite_join_result_chat_invi_dacd8245b982')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_messages_chat_invite_join_result');
    }
};
