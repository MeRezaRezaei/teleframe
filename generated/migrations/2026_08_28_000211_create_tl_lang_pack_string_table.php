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
        Schema::create('tl_lang_pack_string', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_lang_pack_string_lang_pack_string', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_lang_pack_string')->cascadeOnDelete();
            $table->text('tl_key');
            $table->text('tl_value');
            $table->timestamps();
        });
        Schema::create('tl_lang_pack_string_lang_pack_string_deleted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_lang_pack_string')->cascadeOnDelete();
            $table->text('tl_key');
            $table->timestamps();
        });
        Schema::create('tl_lang_pack_string_lang_pack_string_pluralized', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_lang_pack_string')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('tl_key');
            $table->text('zero_value')->nullable();
            $table->text('one_value')->nullable();
            $table->text('two_value')->nullable();
            $table->text('few_value')->nullable();
            $table->text('many_value')->nullable();
            $table->text('other_value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_lang_pack_string_lang_pack_string_pluralized');
        Schema::dropIfExists('tl_lang_pack_string_lang_pack_string_deleted');
        Schema::dropIfExists('tl_lang_pack_string_lang_pack_string');
        Schema::dropIfExists('tl_lang_pack_string');
    }
};
