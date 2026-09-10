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
        Schema::create('tl_search_posts_flood_search_posts_flood', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('query_is_free')->default(false);
            $table->integer('total_daily')->nullable();
            $table->integer('remains')->nullable();
            $table->integer('wait_till')->nullable();
            $table->bigInteger('stars_amount')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4635ea47a58daba52b7236a0');
            $table->index('account_id', 'ix_fb7f142e8cf4c01c1324c23e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_search_posts_flood_search_posts_flood');
    }
};
