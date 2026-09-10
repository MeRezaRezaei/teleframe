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
        Schema::create('tl_input_encrypted_chat_input_encrypted_chat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('chat_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6534fdad4b4ac71a79b84a1c');
            $table->index('account_id', 'ix_960039f6a46483a822c099b3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_encrypted_chat_input_encrypted_chat');
    }
};
