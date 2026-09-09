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
        Schema::create('tl_input_encrypted_chat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_253c25d652ea9baf1cfbbc69');
            $table->index('account_id', 'ix_bb1ae10f8db1f35f3ddae8ed');
        });
        Schema::create('tl_input_encrypted_chat_input_encrypted_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_encrypted_chat')->cascadeOnDelete();
            $table->integer('chat_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_960039f6a46483a822c099b3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_encrypted_chat_input_encrypted_chat');
        Schema::dropIfExists('tl_input_encrypted_chat');
    }
};
