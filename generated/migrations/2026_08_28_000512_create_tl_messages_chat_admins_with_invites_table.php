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
        Schema::create('tl_messages_chat_admins_with_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_05d7821732c99e89b9b05b4c');
            $table->index('account_id', 'ix_cc3694279bba74eb82bd334a');
        });
        Schema::create('tl_messages_chat_admins_with_invites_chat_adm_f8ed6a6ff14e', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_chat_admins_with_invites')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_840424317478cf55c7c5b60d');
        });
        Schema::create('tl_messages_chat_admins_with_invites_chat_adm_b1767129b10c', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_chat_admins_with_invites_chat_adm_f8ed6a6ff14e')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2be06f783999c19f30ba');
            $table->index('account_id', 'ix_5c2027cc60e455389a28371f');
        });
        Schema::create('tl_messages_chat_admins_with_invites_chat_adm_3175fc4f3da2', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_chat_admins_with_invites_chat_adm_f8ed6a6ff14e')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_messages_chat_admins_with_invites');
    }
};
