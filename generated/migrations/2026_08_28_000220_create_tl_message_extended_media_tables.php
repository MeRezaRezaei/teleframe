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
        Schema::create('tl_message_extended_media_message_extended_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('media')->nullable();
            $table->index('media', 'ix_b6c3efb7eee318dde744890f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0a19874c375d32d41a454fa2');
            $table->index('account_id', 'ix_aeb5f1ec1b674a4596bd604c');
        });
        Schema::create('tl_message_extended_media_message_extended_media_preview', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->bigInteger('thumb')->nullable();
            $table->index('thumb', 'ix_9dab79a0e541e8dd35b5fe43');
            $table->integer('video_duration')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_00c0adf5b80a37b500f427af');
            $table->index('account_id', 'ix_3843c809aee78b92cfdcbb02');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_extended_media_message_extended_media_preview');
        Schema::dropIfExists('tl_message_extended_media_message_extended_media');
    }
};
