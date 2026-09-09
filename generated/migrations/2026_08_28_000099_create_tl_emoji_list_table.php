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
        Schema::create('tl_emoji_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b96c2bb280450b04c7313440');
            $table->index('account_id', 'ix_fb9a9c78efba3c686e89e913');
        });
        Schema::create('tl_emoji_list_emoji_list', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_list')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ceb88ed316a1e0bbb8d21ceb');
        });
        Schema::create('tl_emoji_list_emoji_list__document_id', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_emoji_list_emoji_list')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_26b260c73390406b3446');
            $table->index('account_id', 'ix_ee1eac8d9ec25d311299edf5');
        });
        Schema::create('tl_emoji_list_emoji_list_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_list')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6aebbcccf05eafb629d2a4ad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_emoji_list_emoji_list_not_modified');
        Schema::dropIfExists('tl_emoji_list_emoji_list__document_id');
        Schema::dropIfExists('tl_emoji_list_emoji_list');
        Schema::dropIfExists('tl_emoji_list');
    }
};
