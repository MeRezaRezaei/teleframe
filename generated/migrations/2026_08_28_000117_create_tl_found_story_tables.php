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
        Schema::create('tl_found_story_found_story', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_17a2766834cb6356958353ce');
            $table->bigInteger('story')->nullable();
            $table->index('story', 'ix_d2fc430b60af80eb165757b5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c2a9b22092a7975d32023c00');
            $table->index('account_id', 'ix_5d734832dddd2b74590d7a55');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_found_story_found_story');
    }
};
