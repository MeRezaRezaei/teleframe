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
        Schema::create('tl_messages_transcribed_audio_transcribed_audio', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('pending')->default(false);
            $table->bigInteger('transcription_id');
            $table->index('transcription_id', 'ix_34513b4634d72631dd82e016');
            $table->text('text');
            $table->integer('trial_remains_num')->nullable();
            $table->integer('trial_remains_until_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bce0cff85fe67685310ae32b');
            $table->index('account_id', 'ix_b4d23ff6021666c3c0d35428');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_transcribed_audio_transcribed_audio');
    }
};
