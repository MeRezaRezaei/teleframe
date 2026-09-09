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
        Schema::create('tl_channel_admin_log_event', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a5be1c31c00501e208213155');
            $table->index('account_id', 'ix_c45db7c740a498990361127c');
        });
        Schema::create('tl_channel_admin_log_event_channel_admin_log_event', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->integer('date');
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_17979102cbe69156de86ba73');
            $table->uuid('action');
            $table->index('action', 'ix_577b1a4f230df17af461b11f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e66e0a228cdc4342a8534d70');
            $table->unique(['account_id', 'tl_id'], 'ux_088665efce16bb43b723');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channel_admin_log_event_channel_admin_log_event');
        Schema::dropIfExists('tl_channel_admin_log_event');
    }
};
