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
        Schema::create('tl_connected_bot', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_d7ae95c1ed3871be765c1229');
            $table->index('account_id', 'ix_777414d95652ab2e1e0af511');
        });
        Schema::create('tl_connected_bot_connected_bot', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_connected_bot')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('bot_id');
            $table->index('bot_id', 'ix_b915a9358c8de9a322238a79');
            $table->uuid('recipients');
            $table->index('recipients', 'ix_3346ad8d06fdb2b9dd1a5617');
            $table->uuid('rights');
            $table->index('rights', 'ix_9ff9dedcb1cd0010ce0093f3');
            $table->text('device')->nullable();
            $table->integer('date')->nullable();
            $table->text('location')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_af549b0e15cf5f6b30d52588');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_connected_bot_connected_bot');
        Schema::dropIfExists('tl_connected_bot');
    }
};
