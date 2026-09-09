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
        Schema::create('tl_search_posts_flood', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b627b41c797471752d92cbd4');
            $table->index('account_id', 'ix_f03b3388d76f240cd540b7c9');
        });
        Schema::create('tl_search_posts_flood_search_posts_flood', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_search_posts_flood')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('query_is_free')->default(false);
            $table->integer('total_daily');
            $table->integer('remains');
            $table->integer('wait_till')->nullable();
            $table->bigInteger('stars_amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fb7f142e8cf4c01c1324c23e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_search_posts_flood_search_posts_flood');
        Schema::dropIfExists('tl_search_posts_flood');
    }
};
