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
        Schema::create('tl_phone_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ae46e214587454d555ac2560');
            $table->index('account_id', 'ix_92e8a1d2522d6796bfde99ac');
        });
        Schema::create('tl_phone_call_phone_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('p2p_allowed')->default(false);
            $table->boolean('video')->default(false);
            $table->boolean('conference_supported')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->index('admin_id', 'ix_55a1b332e673647a4087cb4e');
            $table->bigInteger('participant_id');
            $table->index('participant_id', 'ix_c82eec0a868fd7b3b280341b');
            $table->binary('g_a_or_b');
            $table->bigInteger('key_fingerprint');
            $table->uuid('protocol');
            $table->index('protocol', 'ix_5cbd2b96d81e593bc267da1f');
            $table->integer('start_date');
            $table->uuid('custom_parameters')->nullable();
            $table->index('custom_parameters', 'ix_994e5a357be9f8c9070c19d3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_631985dcead71917ce5703c4');
            $table->unique(['account_id', 'tl_id'], 'ux_8aff4480f28726f25f4a');
        });
        Schema::create('tl_phone_call_phone_call__connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_call_phone_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4916b96e04170d7891eb');
            $table->index('account_id', 'ix_6d6f80c355b7295d754c643b');
        });
        Schema::create('tl_phone_call_phone_call_accepted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('video')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->index('admin_id', 'ix_3a37650df8badaa4bcb6409b');
            $table->bigInteger('participant_id');
            $table->index('participant_id', 'ix_77af5e82c94b03ca8c74374e');
            $table->binary('g_b');
            $table->uuid('protocol');
            $table->index('protocol', 'ix_6ccf1222bf23ba00d8d9a647');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ee666e6bbf3c36d512bdab16');
            $table->unique(['account_id', 'tl_id'], 'ux_a958d870971be643a338');
        });
        Schema::create('tl_phone_call_phone_call_discarded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('need_rating')->default(false);
            $table->boolean('need_debug')->default(false);
            $table->boolean('video')->default(false);
            $table->bigInteger('tl_id');
            $table->uuid('reason')->nullable();
            $table->index('reason', 'ix_8b3750eb27f191d890e38c24');
            $table->integer('duration')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9267c6474fa8b0dda3eb6392');
            $table->unique(['account_id', 'tl_id'], 'ux_f72a63a46085bf597b30');
        });
        Schema::create('tl_phone_call_phone_call_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c8ec4a53f252d4ebf8b65981');
            $table->unique(['account_id', 'tl_id'], 'ux_af72805bd5b94b76bae1');
        });
        Schema::create('tl_phone_call_phone_call_requested', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('video')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->index('admin_id', 'ix_6d6d69b923ae8c746f885b45');
            $table->bigInteger('participant_id');
            $table->index('participant_id', 'ix_99a3edf33c3e8ec8ebf2337d');
            $table->binary('g_a_hash');
            $table->uuid('protocol');
            $table->index('protocol', 'ix_23ada69b299a36722b9eeb0a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7a09695f454a015b3898766b');
            $table->unique(['account_id', 'tl_id'], 'ux_cf152c1b3961fef145a4');
        });
        Schema::create('tl_phone_call_phone_call_waiting', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('video')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->index('admin_id', 'ix_1b6f9ca0908f7e4de9d2e9db');
            $table->bigInteger('participant_id');
            $table->index('participant_id', 'ix_f784c678280edf8842ecee6a');
            $table->uuid('protocol');
            $table->index('protocol', 'ix_6980e709e73d4daba938c639');
            $table->integer('receive_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9cea1f98555bc3125a9e0322');
            $table->unique(['account_id', 'tl_id'], 'ux_db4bf240c1dd58d1f6bc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_call_phone_call_waiting');
        Schema::dropIfExists('tl_phone_call_phone_call_requested');
        Schema::dropIfExists('tl_phone_call_phone_call_empty');
        Schema::dropIfExists('tl_phone_call_phone_call_discarded');
        Schema::dropIfExists('tl_phone_call_phone_call_accepted');
        Schema::dropIfExists('tl_phone_call_phone_call__connections');
        Schema::dropIfExists('tl_phone_call_phone_call');
        Schema::dropIfExists('tl_phone_call');
    }
};
