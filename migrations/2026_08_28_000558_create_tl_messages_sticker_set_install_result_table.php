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
        Schema::create('tl_messages_sticker_set_install_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_f073bf30f1e8ec5d0ffeabf6');
            $table->index('account_id', 'ix_588bae844541c02e0baa534a');
        });
        Schema::create('tl_messages_sticker_set_install_result_sticke_41df7d9fd353', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_sticker_set_install_result')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8afe67ad63399b0aba266eea');
        });
        Schema::create('tl_messages_sticker_set_install_result_sticke_5c07fbe66093', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_sticker_set_install_result_sticke_41df7d9fd353')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_146ad0ee9bdfa686629b');
            $table->index('account_id', 'ix_f38d1b13eabf5ae4db3a079a');
        });
        Schema::create('tl_messages_sticker_set_install_result_sticke_16d19216a0b6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_sticker_set_install_result')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_80cf0b38aa032ac347f19f35');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_sticker_set_install_result_sticke_16d19216a0b6');
        Schema::dropIfExists('tl_messages_sticker_set_install_result_sticke_5c07fbe66093');
        Schema::dropIfExists('tl_messages_sticker_set_install_result_sticke_41df7d9fd353');
        Schema::dropIfExists('tl_messages_sticker_set_install_result');
    }
};
