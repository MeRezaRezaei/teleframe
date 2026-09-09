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
        Schema::create('tl_lang_pack_difference', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_76ec84c847a46d295d5d741c');
            $table->index('account_id', 'ix_7ce54466911f526ecbbb3369');
        });
        Schema::create('tl_lang_pack_difference_lang_pack_difference', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_lang_pack_difference')->cascadeOnDelete();
            $table->text('lang_code');
            $table->integer('from_version');
            $table->integer('version');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f430674713de1c5f9ef435e9');
        });
        Schema::create('tl_lang_pack_difference_lang_pack_difference__strings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_lang_pack_difference_lang_pack_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b0d6c7f5f39fcd4769d4');
            $table->index('account_id', 'ix_57ad79242b3ababc5b0c3618');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_lang_pack_difference_lang_pack_difference__strings');
        Schema::dropIfExists('tl_lang_pack_difference_lang_pack_difference');
        Schema::dropIfExists('tl_lang_pack_difference');
    }
};
