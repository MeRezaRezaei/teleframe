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
        Schema::create('tl_messages_invited_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_351e20d9ab94f2fd5ebf2c25');
            $table->index('account_id', 'ix_9781024e7898e367b7a19608');
        });
        Schema::create('tl_messages_invited_users_invited_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_invited_users')->cascadeOnDelete();
            $table->uuid('updates');
            $table->index('updates', 'ix_cebaf1e37bcf32de13df3993');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_557ca51f659f254eaeba79ee');
        });
        Schema::create('tl_messages_invited_users_invited_users__missing_invitees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_invited_users_invited_users')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4b7d444d6633365359f8');
            $table->index('account_id', 'ix_563a566530dc9774259f6fc1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_invited_users_invited_users__missing_invitees');
        Schema::dropIfExists('tl_messages_invited_users_invited_users');
        Schema::dropIfExists('tl_messages_invited_users');
    }
};
