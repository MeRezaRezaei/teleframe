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
        Schema::create('tl_phone_connection', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_f70c3960d09f40420bc95af1');
            $table->index('account_id', 'ix_4a971ff1a982fce7f583f707');
        });
        Schema::create('tl_phone_connection_phone_connection', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_connection')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('tcp')->default(false);
            $table->bigInteger('tl_id');
            $table->text('ip');
            $table->text('ipv6');
            $table->integer('port');
            $table->binary('peer_tag');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_acbc9804cc446039234e5dd7');
            $table->unique(['account_id', 'tl_id'], 'ux_01be920add314ea84995');
        });
        Schema::create('tl_phone_connection_phone_connection_webrtc', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_connection')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('turn')->default(false);
            $table->boolean('stun')->default(false);
            $table->bigInteger('tl_id');
            $table->text('ip');
            $table->text('ipv6');
            $table->integer('port');
            $table->text('username');
            $table->text('password');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6f86f0b13eae0e91c029fe82');
            $table->unique(['account_id', 'tl_id'], 'ux_805f511d64d317307ca4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_connection_phone_connection_webrtc');
        Schema::dropIfExists('tl_phone_connection_phone_connection');
        Schema::dropIfExists('tl_phone_connection');
    }
};
