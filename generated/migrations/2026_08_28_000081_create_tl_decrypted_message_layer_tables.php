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
        Schema::create('tl_decrypted_message_layer_decrypted_message_layer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('random_bytes')->nullable();
            $table->integer('layer')->nullable();
            $table->integer('in_seq_no')->nullable();
            $table->integer('out_seq_no')->nullable();
            $table->bigInteger('message')->nullable();
            $table->index('message', 'ix_7aff4563657fbe8f54249764');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_336f1999db2b411c25949c4f');
            $table->index('account_id', 'ix_5a72cdabbbc942fe9964a8c5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_decrypted_message_layer_decrypted_message_layer');
    }
};
