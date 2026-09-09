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
        Schema::create('tl_updates_difference', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c1fd15de19bc96e50faf45d6');
            $table->index('account_id', 'ix_e2da9be0be729b1f68f6204f');
        });
        Schema::create('tl_updates_difference_difference', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates_difference')->cascadeOnDelete();
            $table->uuid('state');
            $table->index('state', 'ix_34a9617a31bb6e96e9b51186');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_aa28c4ec390fe4e006c84c5c');
        });
        Schema::create('tl_updates_difference_difference__new_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_984b2af625660e04e6db');
            $table->index('account_id', 'ix_ddde33f2b54cd554fcba7d37');
        });
        Schema::create('tl_updates_difference_difference__new_encrypted_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a753c9ceec7276a92004');
            $table->index('account_id', 'ix_3926e1c382b2a59bdceae3eb');
        });
        Schema::create('tl_updates_difference_difference__other_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_42087b8394f822c27d04');
            $table->index('account_id', 'ix_87fdfdb7a80c46717d7a78e8');
        });
        Schema::create('tl_updates_difference_difference__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1e51c017a1272b9db7f2');
            $table->index('account_id', 'ix_bfb75d0ef817326dcb4f10cf');
        });
        Schema::create('tl_updates_difference_difference__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_15bc8d11547fcb45b9b2');
            $table->index('account_id', 'ix_2069747ac4067082d654bbac');
        });
        Schema::create('tl_updates_difference_difference_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates_difference')->cascadeOnDelete();
            $table->integer('date');
            $table->integer('seq');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_44304b3470fb58af106a3195');
        });
        Schema::create('tl_updates_difference_difference_slice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates_difference')->cascadeOnDelete();
            $table->uuid('intermediate_state');
            $table->index('intermediate_state', 'ix_9af9e96a192a56c59a717049');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5f7a6f39e3f6afa56f0d6f05');
        });
        Schema::create('tl_updates_difference_difference_slice__new_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8a197ff68922b821fd18');
            $table->index('account_id', 'ix_7df12042a4760bba01c4fb27');
        });
        Schema::create('tl_updates_difference_difference_slice__new_e_864e87a82655', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4695944bc26300827182');
            $table->index('account_id', 'ix_43b14c093cec7f6269ab2074');
        });
        Schema::create('tl_updates_difference_difference_slice__other_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d12bf810563b935b9de6');
            $table->index('account_id', 'ix_b7f5222a41750d9b023e3a9f');
        });
        Schema::create('tl_updates_difference_difference_slice__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9beb1d81e061c1e94742');
            $table->index('account_id', 'ix_95d06428f3799d06f5b78534');
        });
        Schema::create('tl_updates_difference_difference_slice__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c94152998cdebdaea062');
            $table->index('account_id', 'ix_f338cf4f1633310096ecdde2');
        });
        Schema::create('tl_updates_difference_difference_too_long', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates_difference')->cascadeOnDelete();
            $table->integer('pts');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_53c6966ebc8ab02ca3947bab');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_updates_difference_difference_too_long');
        Schema::dropIfExists('tl_updates_difference_difference_slice__users');
        Schema::dropIfExists('tl_updates_difference_difference_slice__chats');
        Schema::dropIfExists('tl_updates_difference_difference_slice__other_updates');
        Schema::dropIfExists('tl_updates_difference_difference_slice__new_e_864e87a82655');
        Schema::dropIfExists('tl_updates_difference_difference_slice__new_messages');
        Schema::dropIfExists('tl_updates_difference_difference_slice');
        Schema::dropIfExists('tl_updates_difference_difference_empty');
        Schema::dropIfExists('tl_updates_difference_difference__users');
        Schema::dropIfExists('tl_updates_difference_difference__chats');
        Schema::dropIfExists('tl_updates_difference_difference__other_updates');
        Schema::dropIfExists('tl_updates_difference_difference__new_encrypted_messages');
        Schema::dropIfExists('tl_updates_difference_difference__new_messages');
        Schema::dropIfExists('tl_updates_difference_difference');
        Schema::dropIfExists('tl_updates_difference');
    }
};
