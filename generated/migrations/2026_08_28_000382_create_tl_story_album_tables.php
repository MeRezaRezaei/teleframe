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
        Schema::create('tl_story_album_story_album', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('album_id')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('icon_photo')->nullable();
            $table->index('icon_photo', 'ix_219b16398fc17f61ed34e914');
            $table->bigInteger('icon_video')->nullable();
            $table->index('icon_video', 'ix_f6273fe494b6665ab998a920');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_25f22760fa2586b6eebacd66');
            $table->index('account_id', 'ix_65ea0acd4953e6cd6e90b778');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_story_album_story_album');
    }
};
