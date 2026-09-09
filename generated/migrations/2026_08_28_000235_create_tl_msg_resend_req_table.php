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
            $table->index('constructor_id', 'ix_cb3d767a6f6312245f7fe5d7');
            $table->index('account_id', 'ix_c883a1ab11a3e2b0926fcccd');
        });
        Schema::create('tl_msg_resend_req_msg_resend_ans_req', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_msg_resend_req')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5a2320b66216e66a6720c880');
        });
        Schema::create('tl_msg_resend_req_msg_resend_ans_req__msg_ids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_msg_resend_req_msg_resend_ans_req')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0524d1bd359220d17c03');
            $table->index('account_id', 'ix_2d0af653b10fa8dcb073ffc0');
        });
        Schema::create('tl_msg_resend_req_msg_resend_req', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_msg_resend_req')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_29a6e566ca6030319ad8f1f5');
        });
        Schema::create('tl_msg_resend_req_msg_resend_req__msg_ids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_msg_resend_req_msg_resend_req')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_19638acc525ba228ac86');
            $table->index('account_id', 'ix_944202a8333b00ee8ac8c2da');
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
