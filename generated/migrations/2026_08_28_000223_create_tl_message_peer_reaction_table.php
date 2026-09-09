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
            $table->index('constructor_id', 'ix_e412d4868590eb405df672e1');
            $table->index('account_id', 'ix_61a1ddaab665abfb6c5226dd');
        });
        Schema::create('tl_message_peer_reaction_message_peer_reaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_peer_reaction')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('big')->default(false);
            $table->boolean('unread')->default(false);
            $table->boolean('my')->default(false);
            $table->bigInteger('peer_id');
            $table->index('peer_id', 'ix_c6e957a089a96a2103566a2c');
            $table->integer('date');
            $table->uuid('reaction');
            $table->index('reaction', 'ix_a4a5fd224c66e627eaeda37a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4c76387b454ebc5a03ad2129');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_peer_reaction_message_peer_reaction');
        Schema::dropIfExists('tl_message_peer_reaction');
    }
};
