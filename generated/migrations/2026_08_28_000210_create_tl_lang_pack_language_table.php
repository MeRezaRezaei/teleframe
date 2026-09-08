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
        Schema::create('tl_lang_pack_language', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_lang_pack_language_lang_pack_language', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_lang_pack_language')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('official')->default(false);
            $table->boolean('rtl')->default(false);
            $table->boolean('beta')->default(false);
            $table->text('name');
            $table->text('native_name');
            $table->text('lang_code');
            $table->text('base_lang_code')->nullable();
            $table->text('plural_code');
            $table->integer('strings_count');
            $table->integer('translated_count');
            $table->text('translations_url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_lang_pack_language_lang_pack_language');
        Schema::dropIfExists('tl_lang_pack_language');
    }
};
