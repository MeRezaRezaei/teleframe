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
        Schema::create('tl_help_country_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_help_country_code_country_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_country_code')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('country_code');
            $table->timestamps();
        });
        Schema::create('tl_help_country_code_country_code__prefixes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_country_code_country_code')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_de54b13ef471252ae783');
        });
        Schema::create('tl_help_country_code_country_code__patterns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_country_code_country_code')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_99efab01b1792a0c5832');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_country_code_country_code__patterns');
        Schema::dropIfExists('tl_help_country_code_country_code__prefixes');
        Schema::dropIfExists('tl_help_country_code_country_code');
        Schema::dropIfExists('tl_help_country_code');
    }
};
