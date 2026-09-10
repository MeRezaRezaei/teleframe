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
        Schema::create('tl_stories_can_send_story_count_can_send_story_count', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count_remains')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_49ea0716a8d2afe1e7eee371');
            $table->index('account_id', 'ix_e5f5436b62fb418306a62846');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_can_send_story_count_can_send_story_count');
    }
};
