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
        Schema::create('tl_payments_checked_gift_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_144aa090d5023328b79fe318');
            $table->index('account_id', 'ix_75860b773ad437c9a91590af');
        });
        Schema::create('tl_payments_checked_gift_code_checked_gift_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_checked_gift_code')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('via_giveaway')->default(false);
            $table->bigInteger('from_id')->nullable();
            $table->index('from_id', 'ix_c928b3816834f8c7bf7475bb');
            $table->integer('giveaway_msg_id')->nullable();
            $table->bigInteger('to_id')->nullable();
            $table->index('to_id', 'ix_b2ee9dd37dc0a0c09c238284');
            $table->integer('date');
            $table->integer('days');
            $table->integer('used_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_354dea51ff0a552967088e7c');
        });
        Schema::create('tl_payments_checked_gift_code_checked_gift_code__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_checked_gift_code_checked_gift_code')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7f28bc681534f97ad1ea');
            $table->index('account_id', 'ix_8ce6ae73ca9c366ff49fb14a');
        });
        Schema::create('tl_payments_checked_gift_code_checked_gift_code__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_checked_gift_code_checked_gift_code')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c30302d6aeb60219fd31');
            $table->index('account_id', 'ix_54fcc2a54cea12b670d215f8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_checked_gift_code_checked_gift_code__users');
        Schema::dropIfExists('tl_payments_checked_gift_code_checked_gift_code__chats');
        Schema::dropIfExists('tl_payments_checked_gift_code_checked_gift_code');
        Schema::dropIfExists('tl_payments_checked_gift_code');
    }
};
