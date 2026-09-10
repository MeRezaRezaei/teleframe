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
        Schema::create('tl_messages_chat_admins_with_invites_chat_adm_f8ed6a6ff14e', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_cc798a1a3ee2ed27fd8119ad');
            $table->index('account_id', 'ix_840424317478cf55c7c5b60d');
        });
        Schema::create('tl_messages_chat_admins_with_invites_chat_adm_b1767129b10c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_e6428378d950dee4c9005633')->references('id')->on('tl_messages_chat_admins_with_invites_chat_adm_f8ed6a6ff14e')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2be06f783999c19f30ba');
            $table->index('account_id', 'ix_5c2027cc60e455389a28371f');
        });
        Schema::create('tl_messages_chat_admins_with_invites_chat_adm_3175fc4f3da2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_95b492a760dc9494453cd435')->references('id')->on('tl_messages_chat_admins_with_invites_chat_adm_f8ed6a6ff14e')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_978bf02232b533211de9');
            $table->index('account_id', 'ix_a264566d9940bed8e8568d5a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_chat_admins_with_invites_chat_adm_3175fc4f3da2');
        Schema::dropIfExists('tl_messages_chat_admins_with_invites_chat_adm_b1767129b10c');
        Schema::dropIfExists('tl_messages_chat_admins_with_invites_chat_adm_f8ed6a6ff14e');
    }
};
