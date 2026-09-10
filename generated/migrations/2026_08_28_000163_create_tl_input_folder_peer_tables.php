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
        Schema::create('tl_input_folder_peer_input_folder_peer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_14b06a91d6180a3b15b3858b');
            $table->integer('folder_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6304158058e413ccedbeaf5f');
            $table->index('account_id', 'ix_e29594de01b06df28f6e2a75');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_folder_peer_input_folder_peer');
    }
};
