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
        Schema::create('tl_rpc_result_rpc_result', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('req_msg_id')->nullable();
            $table->index('req_msg_id', 'ix_d4aea8f76ffcfbb64b995e57');
            $table->bigInteger('result')->nullable();
            $table->index('result', 'ix_90e32d40f5bd07d5ab40ba22');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_42b03b37d0a262a047f246b1');
            $table->index('account_id', 'ix_47b17ef207f475e5fe8a15e2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_rpc_result_rpc_result');
    }
};
