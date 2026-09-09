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
        Schema::create('tl_sticker_pack', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_db364256714a228d937d1a91');
            $table->index('account_id', 'ix_fec12c075e0157404ad25dae');
        });
        Schema::create('tl_sticker_pack_sticker_pack', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sticker_pack')->cascadeOnDelete();
            $table->text('emoticon');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c9f079c54c65bffc75a4d0a8');
        });
        Schema::create('tl_sticker_pack_sticker_pack__documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_sticker_pack_sticker_pack')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4b25d3e130f6810d7009');
            $table->index('account_id', 'ix_f88a80e6d69e00f89d74886b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sticker_pack_sticker_pack__documents');
        Schema::dropIfExists('tl_sticker_pack_sticker_pack');
        Schema::dropIfExists('tl_sticker_pack');
    }
};
