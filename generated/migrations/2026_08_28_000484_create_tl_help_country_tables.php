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
        Schema::create('tl_help_country_country', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('hidden')->default(false);
            $table->text('iso2')->nullable();
            $table->text('default_name')->nullable();
            $table->text('name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e094c9003dfa01638102c517');
            $table->index('account_id', 'ix_ed62e51ae9b5b79ae8c223b9');
        });
        Schema::create('tl_help_country_country__country_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_2a0a9cd9673857106b18ac14')->references('id')->on('tl_help_country_country')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8c506991092f233e3b14');
            $table->index('account_id', 'ix_4dfa388eabdaf02589745500');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_country_country__country_codes');
        Schema::dropIfExists('tl_help_country_country');
    }
};
