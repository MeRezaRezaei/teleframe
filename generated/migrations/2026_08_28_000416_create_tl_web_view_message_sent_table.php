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
            $table->index('constructor_id', 'ix_f247b1cd3fc7bb9848594e90');
            $table->index('account_id', 'ix_45445c2d08760ee2282cb5b9');
        });
        Schema::create('tl_web_view_message_sent_web_view_message_sent', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_view_message_sent')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('msg_id')->nullable();
            $table->index('msg_id', 'ix_1d1869e387cc9efec4e93676');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7c3f6a272859126aad144d6d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_view_message_sent_web_view_message_sent');
        Schema::dropIfExists('tl_web_view_message_sent');
    }
};
