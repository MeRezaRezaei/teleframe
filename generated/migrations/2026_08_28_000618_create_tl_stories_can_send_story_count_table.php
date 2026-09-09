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
        Schema::create('tl_stories_can_send_story_count', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e3a16b8fc954254c7e896925');
            $table->index('account_id', 'ix_355a642f7033e747378d432d');
        });
        Schema::create('tl_stories_can_send_story_count_can_send_story_count', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_can_send_story_count')->cascadeOnDelete();
            $table->integer('count_remains');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e5f5436b62fb418306a62846');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_can_send_story_count_can_send_story_count');
        Schema::dropIfExists('tl_stories_can_send_story_count');
    }
};
