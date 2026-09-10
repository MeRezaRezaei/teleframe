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
        Schema::create('tl_msgs_state_req_msgs_state_req', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_964d64f9a7ec0125da6f04c7');
            $table->index('account_id', 'ix_02051fc1c2b2c43ca04306f0');
        });
        Schema::create('tl_msgs_state_req_msgs_state_req__msg_ids', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_msgs_state_req_msgs_state_req', 'id', 'fk_1777dc5b747d61e454ec733f')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_fb70c184aeaa18f862bb');
            $table->index('account_id', 'ix_d808f382e2b5656106fca2b3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_msgs_state_req_msgs_state_req__msg_ids');
        Schema::dropIfExists('tl_msgs_state_req_msgs_state_req');
    }
};
