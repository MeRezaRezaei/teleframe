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
        Schema::create('tl_messages_sticker_set_install_result_sticke_41df7d9fd353', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a778771856a33d918b13cf84');
            $table->index('account_id', 'ix_8afe67ad63399b0aba266eea');
        });
        Schema::create('tl_messages_sticker_set_install_result_sticke_5c07fbe66093', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_f375077086f4c915119f7edc')->references('id')->on('tl_messages_sticker_set_install_result_sticke_41df7d9fd353')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_146ad0ee9bdfa686629b');
            $table->index('account_id', 'ix_f38d1b13eabf5ae4db3a079a');
        });
        Schema::create('tl_messages_sticker_set_install_result_sticke_16d19216a0b6', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e0fcc56b662298314c3617ae');
            $table->index('account_id', 'ix_80cf0b38aa032ac347f19f35');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_sticker_set_install_result_sticke_16d19216a0b6');
        Schema::dropIfExists('tl_messages_sticker_set_install_result_sticke_5c07fbe66093');
        Schema::dropIfExists('tl_messages_sticker_set_install_result_sticke_41df7d9fd353');
    }
};
