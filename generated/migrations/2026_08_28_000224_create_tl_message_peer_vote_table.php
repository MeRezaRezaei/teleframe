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
        Schema::create('tl_message_peer_vote', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_654fdb9e84cfdf6195f219c0');
            $table->index('account_id', 'ix_161bcc0b1260368968dd545d');
        });
        Schema::create('tl_message_peer_vote_message_peer_vote', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_peer_vote')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_278c4334088af47aeb476e04');
            $table->binary('option');
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8deae5318adb51cdb1030c36');
        });
        Schema::create('tl_message_peer_vote_message_peer_vote_input_option', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_peer_vote')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_a4d3435813a55abddaed3f92');
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_609948717366c952533a774c');
        });
        Schema::create('tl_message_peer_vote_message_peer_vote_multiple', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_peer_vote')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_ceb9217358c9c50ea5c85810');
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_29056c6eda53d9ee3171985a');
        });
        Schema::create('tl_message_peer_vote_message_peer_vote_multiple__options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_peer_vote_message_peer_vote_multiple')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->binary('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f606c035184fbaa7746e');
            $table->index('account_id', 'ix_f1989cb1b540ca2e28abc279');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_peer_vote_message_peer_vote_multiple__options');
        Schema::dropIfExists('tl_message_peer_vote_message_peer_vote_multiple');
        Schema::dropIfExists('tl_message_peer_vote_message_peer_vote_input_option');
        Schema::dropIfExists('tl_message_peer_vote_message_peer_vote');
        Schema::dropIfExists('tl_message_peer_vote');
    }
};
