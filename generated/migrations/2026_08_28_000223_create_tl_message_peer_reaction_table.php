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
        Schema::create('tl_message_peer_reaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_message_peer_reaction_message_peer_reaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_peer_reaction')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('big')->default(false);
            $table->boolean('unread')->default(false);
            $table->boolean('my')->default(false);
            $table->uuid('peer_id');
            $table->integer('date');
            $table->uuid('reaction');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_peer_reaction_message_peer_reaction');
        Schema::dropIfExists('tl_message_peer_reaction');
    }
};
