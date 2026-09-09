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
            $table->index('constructor_id', 'ix_d1314e8b9a3f6264d83abccf');
            $table->index('account_id', 'ix_bf19783b61ed6eb479b3279b');
        });
        Schema::create('tl_encrypted_message_encrypted_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_message')->cascadeOnDelete();
            $table->bigInteger('random_id');
            $table->index('random_id', 'ix_fc0c572f6ab8786bce4e7f93');
            $table->integer('chat_id');
            $table->integer('date');
            $table->binary('bytes');
            $table->uuid('file');
            $table->index('file', 'ix_be2bc37d94cfb1696df01088');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_00956ed06f08df096ee8de67');
        });
        Schema::create('tl_encrypted_message_encrypted_message_service', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_message')->cascadeOnDelete();
            $table->bigInteger('random_id');
            $table->index('random_id', 'ix_991b08a27ecfa14827e67b8c');
            $table->integer('chat_id');
            $table->integer('date');
            $table->binary('bytes');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7727a2bf6f3c8f6799f6b897');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_encrypted_message_encrypted_message_service');
        Schema::dropIfExists('tl_encrypted_message_encrypted_message');
        Schema::dropIfExists('tl_encrypted_message');
    }
};
