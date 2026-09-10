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
        Schema::create('tl_msg_resend_req_msg_resend_ans_req', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4b4de54bd3397a997c8622f0');
            $table->index('account_id', 'ix_5a2320b66216e66a6720c880');
        });
        Schema::create('tl_msg_resend_req_msg_resend_ans_req__msg_ids', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_6ac46cf5e62defbc2a043cd0')->references('id')->on('tl_msg_resend_req_msg_resend_ans_req')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0524d1bd359220d17c03');
            $table->index('account_id', 'ix_2d0af653b10fa8dcb073ffc0');
        });
        Schema::create('tl_msg_resend_req_msg_resend_req', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b5856e9a5b313f4559baf51b');
            $table->index('account_id', 'ix_29a6e566ca6030319ad8f1f5');
        });
        Schema::create('tl_msg_resend_req_msg_resend_req__msg_ids', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_70324de9879132a77f93bff0')->references('id')->on('tl_msg_resend_req_msg_resend_req')->cascadeOnDelete();
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
    }
};
