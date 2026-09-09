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
        Schema::create('tl_bad_msg_notification', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1a4dbd1c9784ec4127237984');
            $table->index('account_id', 'ix_cfd9791d09d6411723225897');
        });
        Schema::create('tl_bad_msg_notification_bad_msg_notification', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bad_msg_notification')->cascadeOnDelete();
            $table->bigInteger('bad_msg_id');
            $table->index('bad_msg_id', 'ix_9b575ebd163020b8ea4996aa');
            $table->integer('bad_msg_seqno');
            $table->integer('error_code');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_de2c1dc8493c1bd03396cd68');
        });
        Schema::create('tl_bad_msg_notification_bad_server_salt', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bad_msg_notification')->cascadeOnDelete();
            $table->bigInteger('bad_msg_id');
            $table->index('bad_msg_id', 'ix_5c704390efbb43f17681faad');
            $table->integer('bad_msg_seqno');
            $table->integer('error_code');
            $table->bigInteger('new_server_salt');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e34bdca1491c5537572c3551');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bad_msg_notification_bad_server_salt');
        Schema::dropIfExists('tl_bad_msg_notification_bad_msg_notification');
        Schema::dropIfExists('tl_bad_msg_notification');
    }
};
