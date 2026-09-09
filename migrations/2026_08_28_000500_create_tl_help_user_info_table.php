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
        Schema::create('tl_help_user_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1065ef86cb10724e19d6a91b');
            $table->index('account_id', 'ix_e28b1d2342af7125c04e07f0');
        });
        Schema::create('tl_help_user_info_user_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_user_info')->cascadeOnDelete();
            $table->text('message');
            $table->text('author');
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_27c51566f7d7688e3e826568');
        });
        Schema::create('tl_help_user_info_user_info__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_user_info_user_info')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4f0146700c0203e8ebb5');
            $table->index('account_id', 'ix_ec708059d1ffc9c30d8914ab');
        });
        Schema::create('tl_help_user_info_user_info_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_user_info')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d3c5242ce81d11014d085ca9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_user_info_user_info_empty');
        Schema::dropIfExists('tl_help_user_info_user_info__entities');
        Schema::dropIfExists('tl_help_user_info_user_info');
        Schema::dropIfExists('tl_help_user_info');
    }
};
