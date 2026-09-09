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
        Schema::create('tl_rpc_drop_answer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ad5409422476161b26d31471');
            $table->index('account_id', 'ix_d5e7aacf7cabbfa526d3d1aa');
        });
        Schema::create('tl_rpc_drop_answer_rpc_answer_dropped', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rpc_drop_answer')->cascadeOnDelete();
            $table->bigInteger('msg_id');
            $table->index('msg_id', 'ix_b26d1f71a5cd2fd75f824a42');
            $table->integer('seq_no');
            $table->integer('bytes');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a87fd06e2cf536da8786ccbf');
        });
        Schema::create('tl_rpc_drop_answer_rpc_answer_dropped_running', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rpc_drop_answer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a4b8b3312b713272881cc24d');
        });
        Schema::create('tl_rpc_drop_answer_rpc_answer_unknown', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rpc_drop_answer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d5743fda704bc9e3efad772c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_rpc_drop_answer_rpc_answer_unknown');
        Schema::dropIfExists('tl_rpc_drop_answer_rpc_answer_dropped_running');
        Schema::dropIfExists('tl_rpc_drop_answer_rpc_answer_dropped');
        Schema::dropIfExists('tl_rpc_drop_answer');
    }
};
