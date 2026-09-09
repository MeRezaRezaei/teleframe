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
        Schema::create('tl_emoji_language', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ced07ca2ba28317d46242b87');
            $table->index('account_id', 'ix_850cfba328b7a1328cef57fb');
        });
        Schema::create('tl_emoji_language_emoji_language', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_language')->cascadeOnDelete();
            $table->text('lang_code');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_41bb4d3c4f8cd870e8855944');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_emoji_language_emoji_language');
        Schema::dropIfExists('tl_emoji_language');
    }
};
