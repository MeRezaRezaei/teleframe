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
            $table->index('constructor_id', 'ix_9efeaf25cdf255406c31df69');
            $table->index('account_id', 'ix_09ae7f040c45bd8afef09563');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_b894d313966b', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('exchange_id');
            $table->index('exchange_id', 'ix_40f5cc5f2d271b7e3ae80e04');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8dfbed95f4681d5d28ddbdd7');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_83c8b6a02d65', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('exchange_id');
            $table->index('exchange_id', 'ix_030270ebe05185260fe8ffb2');
            $table->binary('g_b');
            $table->bigInteger('key_fingerprint');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c6c650e69e5d85f670ac89de');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_ef9eacbe15fe', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('exchange_id');
            $table->index('exchange_id', 'ix_93cc6cb1911a65f7410faf94');
            $table->bigInteger('key_fingerprint');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d5c34d816bf57cfba7d75fb3');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_1beea02c6150', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f81ade717fab30bfa9d29e9e');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_e89bdfd0d31d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_decrypted_message_action_decrypted_message_1beea02c6150')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a5013f643833c36ff11e');
            $table->index('account_id', 'ix_60c9ac217129bf458f9abe80');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_6d45161f4eb2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3b997c93296d079a5e1b892f');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_action_noop', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_eb69662f9f85ef8f412445df');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_5c7ef77b5fe3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->integer('layer');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_dd1e21ccec2571e0c6b4f5e6');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_1d196e6db4b7', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_418830fb7848b5d4362d136e');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_eddbfc36281f', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_decrypted_message_action_decrypted_message_1d196e6db4b7')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2c2e3a2700dcc2a7f23e');
            $table->index('account_id', 'ix_8f1806d226611aff8c5bdad1');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_3b1a1a5ea7c0', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('exchange_id');
            $table->index('exchange_id', 'ix_f37bac77395ec94140ddfa64');
            $table->binary('g_a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_aef039a9bec417cb702bbfbe');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_44851f6ed12e', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->integer('start_seq_no');
            $table->integer('end_seq_no');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fa02e969603ec25f02ab4056');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_503f68851191', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2288fa1d5e36690abfc06497');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_039ececa033a', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_decrypted_message_action_decrypted_message_503f68851191')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_15aea8c5dec84876e021');
            $table->index('account_id', 'ix_778216ab925f972fdb5216ac');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_69df6d9bcc2a', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->integer('ttl_seconds');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_22ae0828767ca978ca1f56a2');
        });
        Schema::create('tl_decrypted_message_action_decrypted_message_b0dfd8d01558', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message_action')->cascadeOnDelete();
            $table->uuid('action');
            $table->index('action', 'ix_875f21969f8699579940b877');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_853bcb97f4f19328b7017f0b');
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
