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
        Schema::create('tl_users_saved_music', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a06ae2c7425236301ba2ae61');
            $table->index('account_id', 'ix_c786c554dc01133bba19d91a');
        });
        Schema::create('tl_users_saved_music_saved_music', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_users_saved_music')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0476be35843afb02f5623346');
        });
        Schema::create('tl_users_saved_music_saved_music__documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_users_saved_music_saved_music')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_395c4b5427a38a7327e5');
            $table->index('account_id', 'ix_78ea5c0f0a65e739ca93ff5a');
        });
        Schema::create('tl_users_saved_music_saved_music_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_users_saved_music')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c22ae1b00bfd0ddda516b2ef');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_users_saved_music_saved_music_not_modified');
        Schema::dropIfExists('tl_users_saved_music_saved_music__documents');
        Schema::dropIfExists('tl_users_saved_music_saved_music');
        Schema::dropIfExists('tl_users_saved_music');
    }
};
