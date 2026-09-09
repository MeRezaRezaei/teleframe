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
        Schema::create('tl_story_album', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b246046bdce0353f0f04f64a');
            $table->index('account_id', 'ix_a9ff39c644fab534e369676b');
        });
        Schema::create('tl_story_album_story_album', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_album')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('album_id');
            $table->text('title');
            $table->uuid('icon_photo')->nullable();
            $table->index('icon_photo', 'ix_219b16398fc17f61ed34e914');
            $table->uuid('icon_video')->nullable();
            $table->index('icon_video', 'ix_f6273fe494b6665ab998a920');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_65ea0acd4953e6cd6e90b778');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_story_album_story_album');
        Schema::dropIfExists('tl_story_album');
    }
};
