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
        Schema::create('tl_decrypted_message_action', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_b894d313966b', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('exchange_id');
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_83c8b6a02d65', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('exchange_id');
            $table->binary('g_b');
            $table->bigInteger('key_fingerprint');
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_ef9eacbe15fe', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('exchange_id');
            $table->bigInteger('key_fingerprint');
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_1beea02c6150', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_e89bdfd0d31d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_decrypted_message_action_decrypted_message_1beea02c6150')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_a5013f643833c36ff11e');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_6d45161f4eb2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_action_noop', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_5c7ef77b5fe3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->integer('layer');
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_1d196e6db4b7', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_eddbfc36281f', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_decrypted_message_action_decrypted_message_1d196e6db4b7')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2c2e3a2700dcc2a7f23e');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_3b1a1a5ea7c0', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('exchange_id');
            $table->binary('g_a');
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_44851f6ed12e', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->integer('start_seq_no');
            $table->integer('end_seq_no');
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_503f68851191', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_039ececa033a', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_decrypted_message_action_decrypted_message_503f68851191')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_15aea8c5dec84876e021');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_69df6d9bcc2a', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->integer('ttl_seconds');
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_b0dfd8d01558', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->uuid('action');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_b0dfd8d01558');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_69df6d9bcc2a');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_039ececa033a');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_503f68851191');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_44851f6ed12e');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_3b1a1a5ea7c0');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_eddbfc36281f');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_1d196e6db4b7');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_5c7ef77b5fe3');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_action_noop');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_6d45161f4eb2');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_e89bdfd0d31d');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_1beea02c6150');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_ef9eacbe15fe');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_83c8b6a02d65');
        Schema::dropIfExists('tl_decrypted_message_action_decrypted_message_b894d313966b');
        Schema::dropIfExists('tl_decrypted_message_action');
    }
};
