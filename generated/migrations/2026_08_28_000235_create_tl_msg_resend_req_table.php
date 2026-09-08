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
        Schema::create('tl_msg_resend_req', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_msg_resend_req_msg_resend_ans_req', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_msg_resend_req')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_msg_resend_req_msg_resend_ans_req__msg_ids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_msg_resend_req_msg_resend_ans_req')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0524d1bd359220d17c03');
        });
        Schema::create('tl_msg_resend_req_msg_resend_req', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_msg_resend_req')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_msg_resend_req_msg_resend_req__msg_ids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_msg_resend_req_msg_resend_req')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_19638acc525ba228ac86');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_msg_resend_req_msg_resend_req__msg_ids');
        Schema::dropIfExists('tl_msg_resend_req_msg_resend_req');
        Schema::dropIfExists('tl_msg_resend_req_msg_resend_ans_req__msg_ids');
        Schema::dropIfExists('tl_msg_resend_req_msg_resend_ans_req');
        Schema::dropIfExists('tl_msg_resend_req');
    }
};
