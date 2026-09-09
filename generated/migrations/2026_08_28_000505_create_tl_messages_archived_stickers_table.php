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
        Schema::create('tl_messages_archived_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9b8f44353056d417e919ab14');
            $table->index('account_id', 'ix_46d4199db8dd295610c93cb6');
        });
        Schema::create('tl_messages_archived_stickers_archived_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_archived_stickers')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1e055c3c4fbd0ddddc857575');
        });
        Schema::create('tl_messages_archived_stickers_archived_stickers__sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_archived_stickers_archived_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_694be764ca12c02a247d');
            $table->index('account_id', 'ix_14042b9a2b348f650de86462');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_archived_stickers_archived_stickers__sets');
        Schema::dropIfExists('tl_messages_archived_stickers_archived_stickers');
        Schema::dropIfExists('tl_messages_archived_stickers');
    }
};
