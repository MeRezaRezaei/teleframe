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
        Schema::create('tl_folder_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_29046a72db43074bda5f4371');
            $table->index('account_id', 'ix_5100629ec097a79387414a3e');
        });
        Schema::create('tl_folder_peer_folder_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_folder_peer')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_043fdf43de8154495a99c3fc');
            $table->integer('folder_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_94d0941b2488b4057c4e265f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_folder_peer_folder_peer');
        Schema::dropIfExists('tl_folder_peer');
    }
};
