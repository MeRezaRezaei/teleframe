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
        Schema::create('tl_input_folder_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_d7aa2fe323f9b53253fd3f41');
            $table->index('account_id', 'ix_a6c7e6665966ed79b9f1da98');
        });
        Schema::create('tl_input_folder_peer_input_folder_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_folder_peer')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_14b06a91d6180a3b15b3858b');
            $table->integer('folder_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e29594de01b06df28f6e2a75');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_folder_peer_input_folder_peer');
        Schema::dropIfExists('tl_input_folder_peer');
    }
};
