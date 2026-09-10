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
        Schema::create('tl_rpc_drop_answer_rpc_answer_dropped', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('msg_id')->nullable();
            $table->index('msg_id', 'ix_b26d1f71a5cd2fd75f824a42');
            $table->integer('seq_no')->nullable();
            $table->integer('bytes')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5f2e691a01b7bf7e20d0427e');
            $table->index('account_id', 'ix_a87fd06e2cf536da8786ccbf');
        });
        Schema::create('tl_rpc_drop_answer_rpc_answer_dropped_running', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_304fff9a57ec8b57f3c10e35');
            $table->index('account_id', 'ix_a4b8b3312b713272881cc24d');
        });
        Schema::create('tl_rpc_drop_answer_rpc_answer_unknown', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1865e93f26b8717ca0d5444c');
            $table->index('account_id', 'ix_d5743fda704bc9e3efad772c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_rpc_drop_answer_rpc_answer_unknown');
        Schema::dropIfExists('tl_rpc_drop_answer_rpc_answer_dropped_running');
        Schema::dropIfExists('tl_rpc_drop_answer_rpc_answer_dropped');
    }
};
