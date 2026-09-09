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
        Schema::create('tl_found_story', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c9d33361f4f8cb9365ebf0bb');
            $table->index('account_id', 'ix_5867dbe505cf373920106551');
        });
        Schema::create('tl_found_story_found_story', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_found_story')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_17a2766834cb6356958353ce');
            $table->uuid('story');
            $table->index('story', 'ix_d2fc430b60af80eb165757b5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5d734832dddd2b74590d7a55');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_found_story_found_story');
        Schema::dropIfExists('tl_found_story');
    }
};
