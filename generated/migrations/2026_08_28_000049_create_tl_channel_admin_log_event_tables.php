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
        Schema::create('tl_channel_admin_log_event_channel_admin_log_event', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_17979102cbe69156de86ba73');
            $table->bigInteger('action')->nullable();
            $table->index('action', 'ix_577b1a4f230df17af461b11f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c3e90bc70385cc8da27a3a91');
            $table->index('account_id', 'ix_e66e0a228cdc4342a8534d70');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channel_admin_log_event_channel_admin_log_event');
    }
};
