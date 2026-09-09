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
        Schema::create('tl_received_notify_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c623837440ef15b7f1910b56');
            $table->index('account_id', 'ix_bc462b97d7f415934a45206b');
        });
        Schema::create('tl_received_notify_message_received_notify_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_received_notify_message')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->integer('flags');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cc90925f96d68c91dbf7b0e8');
            $table->unique(['account_id', 'tl_id'], 'ux_1702d65029e4d17547c4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_received_notify_message_received_notify_message');
        Schema::dropIfExists('tl_received_notify_message');
    }
};
