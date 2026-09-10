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
        Schema::create('tl_messages_exported_chat_invite_exported_chat_invite', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('invite')->nullable();
            $table->index('invite', 'ix_045577008ebf56b87ebd5f0b');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5aced83f1f014e43f5ba35e3');
            $table->index('account_id', 'ix_5449d528b09ccec506f4bc5d');
        });
        Schema::create('tl_messages_exported_chat_invite_exported_cha_0c41e9ae4e71', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_exported_chat_invite_exported_chat_invite', 'id', 'fk_5f7cf3f7f873ffa8b8bd6653')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7c328d56654a15594123');
            $table->index('account_id', 'ix_976694f7165ee094c5cfb70e');
        });
        Schema::create('tl_messages_exported_chat_invite_exported_cha_d180f3d61600', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('invite')->nullable();
            $table->index('invite', 'ix_33d236cc63f4fade37c8f43c');
            $table->bigInteger('new_invite')->nullable();
            $table->index('new_invite', 'ix_b9880912d43ea8c7c20d694e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_15bc2a2aabe5ce82828e5aac');
            $table->index('account_id', 'ix_07f145dd536dac8d4e9ec0d9');
        });
        Schema::create('tl_messages_exported_chat_invite_exported_cha_4638a2b5d812', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_exported_chat_invite_exported_cha_d180f3d61600', 'id', 'fk_63d2d6ebba083cd549703feb')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9c47da4cc2e78d9f031c');
            $table->index('account_id', 'ix_528a1f34aee0a9c2f534f259');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_exported_chat_invite_exported_cha_4638a2b5d812');
        Schema::dropIfExists('tl_messages_exported_chat_invite_exported_cha_d180f3d61600');
        Schema::dropIfExists('tl_messages_exported_chat_invite_exported_cha_0c41e9ae4e71');
        Schema::dropIfExists('tl_messages_exported_chat_invite_exported_chat_invite');
    }
};
