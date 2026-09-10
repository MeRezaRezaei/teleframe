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
        Schema::create('tl_recent_story_recent_story', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('live')->default(false);
            $table->integer('max_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ccf2c1ecd5cf78e60f9e576a');
            $table->index('account_id', 'ix_c613f74eec727e0630b0dc14');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_recent_story_recent_story');
    }
};
