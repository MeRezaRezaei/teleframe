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
        Schema::create('tl_msgs_state_info_msgs_state_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('req_msg_id')->nullable();
            $table->index('req_msg_id', 'ix_f1fa51825a82f4f26c6ee1f5');
            $table->text('info')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_430367f995e91519b815ceb3');
            $table->index('account_id', 'ix_b4aabbf8e2f08821fb6828ee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_msgs_state_info_msgs_state_info');
    }
};
