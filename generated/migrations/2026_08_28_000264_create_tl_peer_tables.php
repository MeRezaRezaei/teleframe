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
        Schema::create('tl_peer_peer_channel', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('channel_id')->nullable();
            $table->index('channel_id', 'ix_6b60abe05ea85685a2b6cbe9');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_722d1635bdc0af2140d829a4');
            $table->index('account_id', 'ix_c07e1465a4d2f03c114f3d81');
        });
        Schema::create('tl_peer_peer_chat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('chat_id')->nullable();
            $table->index('chat_id', 'ix_b8590a6a16287b3f80f7a1c0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_57ea2915ed827d2f4ad73eab');
            $table->index('account_id', 'ix_b1846a27e4ed531124103803');
        });
        Schema::create('tl_peer_peer_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_ceded89f04f207343535c096');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_38581011a3a3a0b0e4c30e7b');
            $table->index('account_id', 'ix_899946ce35ed2753c5236380');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_peer_user');
        Schema::dropIfExists('tl_peer_peer_chat');
        Schema::dropIfExists('tl_peer_peer_channel');
    }
};
