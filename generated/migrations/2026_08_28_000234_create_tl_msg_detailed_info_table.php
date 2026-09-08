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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_msg_detailed_info_msg_detailed_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_msg_detailed_info')->cascadeOnDelete();
            $table->bigInteger('msg_id');
            $table->bigInteger('answer_msg_id');
            $table->integer('bytes');
            $table->integer('status');
            $table->timestamps();
        });
        Schema::create('tl_msg_detailed_info_msg_new_detailed_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_msg_detailed_info')->cascadeOnDelete();
            $table->bigInteger('answer_msg_id');
            $table->integer('bytes');
            $table->integer('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_msg_detailed_info_msg_new_detailed_info');
        Schema::dropIfExists('tl_msg_detailed_info_msg_detailed_info');
        Schema::dropIfExists('tl_msg_detailed_info');
    }
};
