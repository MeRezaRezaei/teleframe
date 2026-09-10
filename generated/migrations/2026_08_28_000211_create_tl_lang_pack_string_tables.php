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
        Schema::create('tl_lang_pack_string_lang_pack_string', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_key')->nullable();
            $table->text('tl_value')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a3cef445e67e0cc41cd51bc7');
            $table->index('account_id', 'ix_8c4d215deaba2e4fdfa35c8d');
        });
        Schema::create('tl_lang_pack_string_lang_pack_string_deleted', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_key')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a9af68664d5814fd557cece4');
            $table->index('account_id', 'ix_0c4c12b0a7e35e0ddbf23548');
        });
        Schema::create('tl_lang_pack_string_lang_pack_string_pluralized', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('tl_key')->nullable();
            $table->text('zero_value')->nullable();
            $table->text('one_value')->nullable();
            $table->text('two_value')->nullable();
            $table->text('few_value')->nullable();
            $table->text('many_value')->nullable();
            $table->text('other_value')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_cc27d4dc8598e166dd744804');
            $table->index('account_id', 'ix_d48d21f11ddb866441d92b79');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_lang_pack_string_lang_pack_string_pluralized');
        Schema::dropIfExists('tl_lang_pack_string_lang_pack_string_deleted');
        Schema::dropIfExists('tl_lang_pack_string_lang_pack_string');
    }
};
