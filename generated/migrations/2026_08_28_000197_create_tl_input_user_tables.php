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
        Schema::create('tl_input_user_input_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_25bbc698fca64b91da96213b');
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3a1802ccb1288bc64d70d211');
            $table->index('account_id', 'ix_8cdb87a6c9f9d5fc007090e6');
        });
        Schema::create('tl_input_user_input_user_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_96961d11440128e16061dd3d');
            $table->index('account_id', 'ix_e0c8e2872f0b607fb876d916');
        });
        Schema::create('tl_input_user_input_user_from_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_669b0744205114d3f2514463');
            $table->integer('msg_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_a4aaebbab2a2429a6ac54d54');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f73b4f1633d0814b6fb714ac');
            $table->index('account_id', 'ix_2427bd94b86009ccf819307c');
        });
        Schema::create('tl_input_user_input_user_self', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_efd06219eb03332c82d3379f');
            $table->index('account_id', 'ix_9516d21a95aa6d554006f33a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_user_input_user_self');
        Schema::dropIfExists('tl_input_user_input_user_from_message');
        Schema::dropIfExists('tl_input_user_input_user_empty');
        Schema::dropIfExists('tl_input_user_input_user');
    }
};
