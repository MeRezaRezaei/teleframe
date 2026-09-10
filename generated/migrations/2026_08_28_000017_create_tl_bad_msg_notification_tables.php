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
        Schema::create('tl_bad_msg_notification_bad_msg_notification', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('bad_msg_id')->nullable();
            $table->index('bad_msg_id', 'ix_9b575ebd163020b8ea4996aa');
            $table->integer('bad_msg_seqno')->nullable();
            $table->integer('error_code')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4bdf825538c12daef3c56a3c');
            $table->index('account_id', 'ix_de2c1dc8493c1bd03396cd68');
        });
        Schema::create('tl_bad_msg_notification_bad_server_salt', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('bad_msg_id')->nullable();
            $table->index('bad_msg_id', 'ix_5c704390efbb43f17681faad');
            $table->integer('bad_msg_seqno')->nullable();
            $table->integer('error_code')->nullable();
            $table->bigInteger('new_server_salt')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_41c871700c05ff69002cf790');
            $table->index('account_id', 'ix_e34bdca1491c5537572c3551');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bad_msg_notification_bad_server_salt');
        Schema::dropIfExists('tl_bad_msg_notification_bad_msg_notification');
    }
};
