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
        Schema::create('tl_page_related_article_page_related_article', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('webpage_id')->nullable();
            $table->index('webpage_id', 'ix_81f12d90d498496eb1da9de1');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo_id')->nullable();
            $table->index('photo_id', 'ix_d3d831ea019d953bdb3f3423');
            $table->text('author')->nullable();
            $table->integer('published_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9707c311b235fc306c5cff32');
            $table->index('account_id', 'ix_f8ada4d226a48db0cbdcf1a6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_related_article_page_related_article');
    }
};
