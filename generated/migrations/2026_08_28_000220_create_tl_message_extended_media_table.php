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
        Schema::create('tl_message_extended_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_2bd927744e846ee34ce25274');
            $table->index('account_id', 'ix_b083065ff769fdd2ec2b62af');
        });
        Schema::create('tl_message_extended_media_message_extended_media', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_extended_media')->cascadeOnDelete();
            $table->uuid('media');
            $table->index('media', 'ix_b6c3efb7eee318dde744890f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_aeb5f1ec1b674a4596bd604c');
        });
        Schema::create('tl_message_extended_media_message_extended_media_preview', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_extended_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->uuid('thumb')->nullable();
            $table->index('thumb', 'ix_9dab79a0e541e8dd35b5fe43');
            $table->integer('video_duration')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3843c809aee78b92cfdcbb02');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_extended_media_message_extended_media_preview');
        Schema::dropIfExists('tl_message_extended_media_message_extended_media');
        Schema::dropIfExists('tl_message_extended_media');
    }
};
