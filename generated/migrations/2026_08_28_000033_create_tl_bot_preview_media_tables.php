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
        Schema::create('tl_bot_preview_media_bot_preview_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('date')->nullable();
            $table->bigInteger('media')->nullable();
            $table->index('media', 'ix_4d8c020a44d561763ef4f01c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4b9a8a2f1f3e405334fb3ac9');
            $table->index('account_id', 'ix_a0fe57d3dee44ff08ce009f9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_preview_media_bot_preview_media');
    }
};
