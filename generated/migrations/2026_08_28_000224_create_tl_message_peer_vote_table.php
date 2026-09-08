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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_message_peer_vote_message_peer_vote', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_peer_vote')->cascadeOnDelete();
            $table->uuid('peer');
            $table->binary('option');
            $table->integer('date');
            $table->timestamps();
        });
        Schema::create('tl_message_peer_vote_message_peer_vote_input_option', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_peer_vote')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('date');
            $table->timestamps();
        });
        Schema::create('tl_message_peer_vote_message_peer_vote_multiple', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_peer_vote')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('date');
            $table->timestamps();
        });
        Schema::create('tl_message_peer_vote_message_peer_vote_multiple__options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_peer_vote_message_peer_vote_multiple')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->binary('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f606c035184fbaa7746e');
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
