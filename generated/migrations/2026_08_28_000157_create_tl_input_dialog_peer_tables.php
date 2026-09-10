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
        Schema::create('tl_input_dialog_peer_input_dialog_peer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_408614efba5924b8e407b81c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f74aad54a2aa6193eea30d43');
            $table->index('account_id', 'ix_905192adeb344d3069cae6e0');
        });
        Schema::create('tl_input_dialog_peer_input_dialog_peer_folder', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('folder_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_de5120743c1a79083a9f5a66');
            $table->index('account_id', 'ix_068da169d9880206f4a01eed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_dialog_peer_input_dialog_peer_folder');
        Schema::dropIfExists('tl_input_dialog_peer_input_dialog_peer');
    }
};
