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
        Schema::create('tl_input_dialog_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_2c61c971d2ef540762f301ed');
            $table->index('account_id', 'ix_1f0d0f8094bc9af24724fe87');
        });
        Schema::create('tl_input_dialog_peer_input_dialog_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_dialog_peer')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_408614efba5924b8e407b81c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_905192adeb344d3069cae6e0');
        });
        Schema::create('tl_input_dialog_peer_input_dialog_peer_folder', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_dialog_peer')->cascadeOnDelete();
            $table->integer('folder_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_068da169d9880206f4a01eed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_dialog_peer_input_dialog_peer_folder');
        Schema::dropIfExists('tl_input_dialog_peer_input_dialog_peer');
        Schema::dropIfExists('tl_input_dialog_peer');
    }
};
