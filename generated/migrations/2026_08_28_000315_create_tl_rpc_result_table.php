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
        Schema::create('tl_rpc_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_2dfc699b0bdc5090084a8aa8');
            $table->index('account_id', 'ix_0f08c9b949cf4db72103b965');
        });
        Schema::create('tl_rpc_result_rpc_result', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rpc_result')->cascadeOnDelete();
            $table->bigInteger('req_msg_id');
            $table->index('req_msg_id', 'ix_d4aea8f76ffcfbb64b995e57');
            $table->uuid('result');
            $table->index('result', 'ix_90e32d40f5bd07d5ab40ba22');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_47b17ef207f475e5fe8a15e2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_rpc_result_rpc_result');
        Schema::dropIfExists('tl_rpc_result');
    }
};
