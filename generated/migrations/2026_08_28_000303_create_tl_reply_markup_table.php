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
        Schema::create('tl_reply_markup', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_18b0394879e47743a6f44c8d');
            $table->index('account_id', 'ix_777869c8fcee9f8bd2cf470e');
        });
        Schema::create('tl_reply_markup_reply_inline_markup', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_reply_markup')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2666cd50daa9db4a9260b1ac');
        });
        Schema::create('tl_reply_markup_reply_inline_markup__rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_reply_markup_reply_inline_markup')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_96bfdbd6f697294f05b6');
            $table->index('account_id', 'ix_f4406595da8e9f93cd3ff5ca');
        });
        Schema::create('tl_reply_markup_reply_keyboard_force_reply', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_reply_markup')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('single_use')->default(false);
            $table->boolean('selective')->default(false);
            $table->text('placeholder')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_70b789afbc8ea7e3700e823c');
        });
        Schema::create('tl_reply_markup_reply_keyboard_hide', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_reply_markup')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('selective')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d70d9ca9bfdbd505f720f956');
        });
        Schema::create('tl_reply_markup_reply_keyboard_markup', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_reply_markup')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('resize')->default(false);
            $table->boolean('single_use')->default(false);
            $table->boolean('selective')->default(false);
            $table->boolean('persistent')->default(false);
            $table->text('placeholder')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1341a80ff37c18b288535d5b');
        });
        Schema::create('tl_reply_markup_reply_keyboard_markup__rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_reply_markup_reply_keyboard_markup')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3a47509de21c5fbf50e6');
            $table->index('account_id', 'ix_c1353a0820effe8ebb396f69');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_reply_markup_reply_keyboard_markup__rows');
        Schema::dropIfExists('tl_reply_markup_reply_keyboard_markup');
        Schema::dropIfExists('tl_reply_markup_reply_keyboard_hide');
        Schema::dropIfExists('tl_reply_markup_reply_keyboard_force_reply');
        Schema::dropIfExists('tl_reply_markup_reply_inline_markup__rows');
        Schema::dropIfExists('tl_reply_markup_reply_inline_markup');
        Schema::dropIfExists('tl_reply_markup');
    }
};
