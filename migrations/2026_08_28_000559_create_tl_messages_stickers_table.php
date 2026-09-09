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
        Schema::create('tl_messages_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9dcf3d57fc579e2d7f727e87');
            $table->index('account_id', 'ix_9b7cf55c230951260498d547');
        });
        Schema::create('tl_messages_stickers_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_stickers')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_18dd427bf4c0ebbc5e05edaa');
        });
        Schema::create('tl_messages_stickers_stickers__stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_stickers_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_05743f8804ce44c71ff4');
            $table->index('account_id', 'ix_09beca342915f21e8e3db8cf');
        });
        Schema::create('tl_messages_stickers_stickers_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_stickers')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8c64820064d273f69762bf42');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_stickers_stickers_not_modified');
        Schema::dropIfExists('tl_messages_stickers_stickers__stickers');
        Schema::dropIfExists('tl_messages_stickers_stickers');
        Schema::dropIfExists('tl_messages_stickers');
    }
};
