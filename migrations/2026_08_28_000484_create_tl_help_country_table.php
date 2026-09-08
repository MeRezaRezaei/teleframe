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
        Schema::create('tl_help_country', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_help_country_country', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_country')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('hidden')->default(false);
            $table->text('iso2');
            $table->text('default_name');
            $table->text('name')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_help_country_country__country_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_country_country')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8c506991092f233e3b14');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_country_country__country_codes');
        Schema::dropIfExists('tl_help_country_country');
        Schema::dropIfExists('tl_help_country');
    }
};
