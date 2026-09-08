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
        Schema::create('tl_encrypted_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_encrypted_message_encrypted_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_message')->cascadeOnDelete();
            $table->bigInteger('random_id');
            $table->integer('chat_id');
            $table->integer('date');
            $table->binary('bytes');
            $table->uuid('file');
            $table->timestamps();
        });
        Schema::create('tl_encrypted_message_encrypted_message_service', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_message')->cascadeOnDelete();
            $table->bigInteger('random_id');
            $table->integer('chat_id');
            $table->integer('date');
            $table->binary('bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_encrypted_message_encrypted_message_service');
        Schema::dropIfExists('tl_encrypted_message_encrypted_message');
        Schema::dropIfExists('tl_encrypted_message');
    }
};
