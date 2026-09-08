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
        Schema::create('tl_web_view_message_sent', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_web_view_message_sent_web_view_message_sent', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_view_message_sent')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('msg_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_view_message_sent_web_view_message_sent');
        Schema::dropIfExists('tl_web_view_message_sent');
    }
};
