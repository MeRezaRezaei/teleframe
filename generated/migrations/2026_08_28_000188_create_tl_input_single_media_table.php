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
        Schema::create('tl_input_single_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5110d98bc416d8ecc959f371');
            $table->index('account_id', 'ix_d6cb9e466ecd9df24ba6246b');
        });
        Schema::create('tl_input_single_media_input_single_media', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_single_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('media');
            $table->index('media', 'ix_38f2db8daad9b06d29927473');
            $table->bigInteger('random_id');
            $table->index('random_id', 'ix_19b05baef7e8c6bfcd4babd9');
            $table->text('message');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_16cf648a6aac777df5443c93');
        });
        Schema::create('tl_input_single_media_input_single_media__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_single_media_input_single_media')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_70e5d717b1880ad3bef7');
            $table->index('account_id', 'ix_949532d4c3d4b739b416fee9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_single_media_input_single_media__entities');
        Schema::dropIfExists('tl_input_single_media_input_single_media');
        Schema::dropIfExists('tl_input_single_media');
    }
};
