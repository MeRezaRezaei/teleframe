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
        Schema::create('tl_messages_transcribed_audio', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b4ad0757ef16509afd991aed');
            $table->index('account_id', 'ix_77f8b1433495fb2438650272');
        });
        Schema::create('tl_messages_transcribed_audio_transcribed_audio', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_transcribed_audio')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pending')->default(false);
            $table->bigInteger('transcription_id');
            $table->index('transcription_id', 'ix_34513b4634d72631dd82e016');
            $table->text('text');
            $table->integer('trial_remains_num')->nullable();
            $table->integer('trial_remains_until_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b4d23ff6021666c3c0d35428');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_transcribed_audio_transcribed_audio');
        Schema::dropIfExists('tl_messages_transcribed_audio');
    }
};
