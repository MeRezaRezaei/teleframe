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
        Schema::create('tl_msg_detailed_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8bf786da061eddb74f6d3ce6');
            $table->index('account_id', 'ix_168968f69cbdd67c1aa65ddf');
        });
        Schema::create('tl_msg_detailed_info_msg_detailed_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_msg_detailed_info')->cascadeOnDelete();
            $table->bigInteger('msg_id');
            $table->index('msg_id', 'ix_32c17c6aba59612124a5f70e');
            $table->bigInteger('answer_msg_id');
            $table->index('answer_msg_id', 'ix_9188be2553d36a49bca250a6');
            $table->integer('bytes');
            $table->integer('status');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1a588cc553034062aae86c4e');
        });
        Schema::create('tl_msg_detailed_info_msg_new_detailed_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_msg_detailed_info')->cascadeOnDelete();
            $table->bigInteger('answer_msg_id');
            $table->index('answer_msg_id', 'ix_3474403ddba9084fad1a7fd4');
            $table->integer('bytes');
            $table->integer('status');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2ca15a0920b981a80a9cadf3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_msg_detailed_info_msg_new_detailed_info');
        Schema::dropIfExists('tl_msg_detailed_info_msg_detailed_info');
        Schema::dropIfExists('tl_msg_detailed_info');
    }
};
