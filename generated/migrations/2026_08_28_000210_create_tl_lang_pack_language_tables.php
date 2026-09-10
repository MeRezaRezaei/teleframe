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
        Schema::create('tl_lang_pack_language_lang_pack_language', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('official')->default(false);
            $table->boolean('rtl')->default(false);
            $table->boolean('beta')->default(false);
            $table->text('name')->nullable();
            $table->text('native_name')->nullable();
            $table->text('lang_code')->nullable();
            $table->text('base_lang_code')->nullable();
            $table->text('plural_code')->nullable();
            $table->integer('strings_count')->nullable();
            $table->integer('translated_count')->nullable();
            $table->text('translations_url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e5e7e590697731bc609c0cc1');
            $table->index('account_id', 'ix_75e87eecc5ca8b9e176ea77f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_lang_pack_language_lang_pack_language');
    }
};
