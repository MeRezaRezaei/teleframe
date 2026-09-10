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
        Schema::create('tl_help_country_code_country_code', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('country_code');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c5d5139934af604d5db2e33e');
            $table->index('account_id', 'ix_0292ec040fe37133322d5495');
        });
        Schema::create('tl_help_country_code_country_code__prefixes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_country_code_country_code', 'id', 'fk_5468d348bd09814ce7c5061f')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_de54b13ef471252ae783');
            $table->index('account_id', 'ix_2fb26cdda9a6bf9b5f82ec8a');
        });
        Schema::create('tl_help_country_code_country_code__patterns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_country_code_country_code', 'id', 'fk_bfc51756738d3b6608438604')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_99efab01b1792a0c5832');
            $table->index('account_id', 'ix_fb719d70e0f1fa72b57e18d8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_country_code_country_code__patterns');
        Schema::dropIfExists('tl_help_country_code_country_code__prefixes');
        Schema::dropIfExists('tl_help_country_code_country_code');
    }
};
