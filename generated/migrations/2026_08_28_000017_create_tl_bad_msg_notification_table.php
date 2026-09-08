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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_bad_msg_notification_bad_msg_notification', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bad_msg_notification')->cascadeOnDelete();
            $table->bigInteger('bad_msg_id');
            $table->integer('bad_msg_seqno');
            $table->integer('error_code');
            $table->timestamps();
        });
        Schema::create('tl_bad_msg_notification_bad_server_salt', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bad_msg_notification')->cascadeOnDelete();
            $table->bigInteger('bad_msg_id');
            $table->integer('bad_msg_seqno');
            $table->integer('error_code');
            $table->bigInteger('new_server_salt');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bad_msg_notification_bad_server_salt');
        Schema::dropIfExists('tl_bad_msg_notification_bad_msg_notification');
        Schema::dropIfExists('tl_bad_msg_notification');
    }
};
