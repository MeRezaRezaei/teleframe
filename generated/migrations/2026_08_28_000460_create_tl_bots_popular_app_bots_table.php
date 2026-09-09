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
        Schema::create('tl_bots_popular_app_bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c58adc914c2196e468166a7b');
            $table->index('account_id', 'ix_b7d8ed479a3347e890afe8bc');
        });
        Schema::create('tl_bots_popular_app_bots_popular_app_bots', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bots_popular_app_bots')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3a5ecb9ac5c2bd33121ce8c2');
        });
        Schema::create('tl_bots_popular_app_bots_popular_app_bots__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bots_popular_app_bots_popular_app_bots')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_09b08ad1aa95887ed468');
            $table->index('account_id', 'ix_5ff7c474bec6ae553a5e40af');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bots_popular_app_bots_popular_app_bots__users');
        Schema::dropIfExists('tl_bots_popular_app_bots_popular_app_bots');
        Schema::dropIfExists('tl_bots_popular_app_bots');
    }
};
