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
        Schema::create('tl_phone_phone_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3e49a288fd4bc01efb250bf1');
            $table->index('account_id', 'ix_c34227e57aec53efff376ac1');
        });
        Schema::create('tl_phone_phone_call_phone_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_phone_call')->cascadeOnDelete();
            $table->uuid('phone_call');
            $table->index('phone_call', 'ix_499fceee457dbd2bf73be9c0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_222e52baaa8c8fe017a77d19');
        });
        Schema::create('tl_phone_phone_call_phone_call__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_phone_call_phone_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_59b1688daddafaa9838e');
            $table->index('account_id', 'ix_88e1312a643321e62ff22667');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_phone_call_phone_call__users');
        Schema::dropIfExists('tl_phone_phone_call_phone_call');
        Schema::dropIfExists('tl_phone_phone_call');
    }
};
