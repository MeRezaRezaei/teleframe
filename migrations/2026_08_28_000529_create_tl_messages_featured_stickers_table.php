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
        Schema::create('tl_messages_featured_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_featured_stickers_featured_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_featured_stickers')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('premium')->default(false);
            $table->bigInteger('hash');
            $table->integer('count');
            $table->timestamps();
        });
        Schema::create('tl_messages_featured_stickers_featured_stickers__sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_featured_stickers_featured_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_99fb8c05f956b245bee0');
        });
        Schema::create('tl_messages_featured_stickers_featured_stickers__unread', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_featured_stickers_featured_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_64d38fd49843570e20c4');
        });
        Schema::create('tl_messages_featured_stickers_featured_sticke_5feaa6a0f11a', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_featured_stickers')->cascadeOnDelete();
            $table->integer('count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_featured_stickers_featured_sticke_5feaa6a0f11a');
        Schema::dropIfExists('tl_messages_featured_stickers_featured_stickers__unread');
        Schema::dropIfExists('tl_messages_featured_stickers_featured_stickers__sets');
        Schema::dropIfExists('tl_messages_featured_stickers_featured_stickers');
        Schema::dropIfExists('tl_messages_featured_stickers');
    }
};
