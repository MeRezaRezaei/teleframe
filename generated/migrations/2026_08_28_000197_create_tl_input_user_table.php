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
        Schema::create('tl_input_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ba566eb182ff2bf9a4b3bd53');
            $table->index('account_id', 'ix_14efd7713d403deab9c25f4e');
        });
        Schema::create('tl_input_user_input_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_user')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_25bbc698fca64b91da96213b');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8cdb87a6c9f9d5fc007090e6');
        });
        Schema::create('tl_input_user_input_user_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_user')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e0c8e2872f0b607fb876d916');
        });
        Schema::create('tl_input_user_input_user_from_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_user')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_669b0744205114d3f2514463');
            $table->integer('msg_id');
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_a4aaebbab2a2429a6ac54d54');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2427bd94b86009ccf819307c');
        });
        Schema::create('tl_input_user_input_user_self', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_user')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9516d21a95aa6d554006f33a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_user_input_user_self');
        Schema::dropIfExists('tl_input_user_input_user_from_message');
        Schema::dropIfExists('tl_input_user_input_user_empty');
        Schema::dropIfExists('tl_input_user_input_user');
        Schema::dropIfExists('tl_input_user');
    }
};
