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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_bots_popular_app_bots_popular_app_bots', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bots_popular_app_bots')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('next_offset')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_bots_popular_app_bots_popular_app_bots__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bots_popular_app_bots_popular_app_bots')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_09b08ad1aa95887ed468');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bots_popular_app_bots_popular_app_bots__users');
        Schema::dropIfExists('tl_bots_popular_app_bots_popular_app_bots');
        Schema::dropIfExists('tl_bots_popular_app_bots');
    }
};
