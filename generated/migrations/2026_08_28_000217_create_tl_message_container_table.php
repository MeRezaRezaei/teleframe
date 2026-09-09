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
        Schema::create('tl_message_container', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_43c189c07cfe3faf3846c4ac');
            $table->index('account_id', 'ix_ad23b1d562bdda1c21b4cfed');
        });
        Schema::create('tl_message_container_msg_container', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_container')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1a9608954e2986845831239e');
        });
        Schema::create('tl_message_container_msg_container__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_container_msg_container')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6d747b95a3609e4ac7aa');
            $table->index('account_id', 'ix_848539fda47c367f28aeb447');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_container_msg_container__messages');
        Schema::dropIfExists('tl_message_container_msg_container');
        Schema::dropIfExists('tl_message_container');
    }
};
