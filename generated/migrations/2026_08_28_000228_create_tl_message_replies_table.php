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
        Schema::create('tl_message_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_53a76106f5090df20f2efb84');
            $table->index('account_id', 'ix_88574d3e81fc3542e534e334');
        });
        Schema::create('tl_message_replies_message_replies', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_replies')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('comments')->default(false);
            $table->integer('replies');
            $table->integer('replies_pts');
            $table->bigInteger('channel_id')->nullable();
            $table->index('channel_id', 'ix_92ced2e6d1d5d6432734c46b');
            $table->integer('max_id')->nullable();
            $table->integer('read_max_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_94941f184101072113724d89');
        });
        Schema::create('tl_message_replies_message_replies__recent_repliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_replies_message_replies')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a8d39e46c83f8dc08989');
            $table->index('account_id', 'ix_580bb60faad624f59d493f6d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_replies_message_replies__recent_repliers');
        Schema::dropIfExists('tl_message_replies_message_replies');
        Schema::dropIfExists('tl_message_replies');
    }
};
