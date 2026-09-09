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
        Schema::create('tl_rpc_error', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e14c0d206d5b6375562d55a7');
            $table->index('account_id', 'ix_95bf3d682e7fff00c3076d7c');
        });
        Schema::create('tl_rpc_error_rpc_error', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rpc_error')->cascadeOnDelete();
            $table->integer('error_code');
            $table->text('error_message');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_af121f017f023b82c47399a7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_rpc_error_rpc_error');
        Schema::dropIfExists('tl_rpc_error');
    }
};
