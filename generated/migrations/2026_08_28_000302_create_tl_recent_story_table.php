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
        Schema::create('tl_recent_story', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_873bac386bfe1605ec9bf41c');
            $table->index('account_id', 'ix_c5ab807418aace823471ceb7');
        });
        Schema::create('tl_recent_story_recent_story', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_recent_story')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('live')->default(false);
            $table->integer('max_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c613f74eec727e0630b0dc14');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_recent_story_recent_story');
        Schema::dropIfExists('tl_recent_story');
    }
};
