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
        Schema::create('tl_sticker_pack_sticker_pack', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('emoticon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f46748c95b0e9d22221fa0bf');
            $table->index('account_id', 'ix_c9f079c54c65bffc75a4d0a8');
        });
        Schema::create('tl_sticker_pack_sticker_pack__documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_sticker_pack_sticker_pack', 'id', 'fk_0833a98e67fab24abfac90a0')->cascadeOnDelete();
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
    }
};
