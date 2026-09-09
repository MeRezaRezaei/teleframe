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
        Schema::create('tl_story_fwd_header', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4d98420af3aeac11c3d41c14');
            $table->index('account_id', 'ix_a245b850b7ecb8b0a2a7ea42');
        });
        Schema::create('tl_story_fwd_header_story_fwd_header', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_fwd_header')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('modified')->default(false);
            $table->bigInteger('tl_from')->nullable();
            $table->index('tl_from', 'ix_1d72871e445adc070163e656');
            $table->text('from_name')->nullable();
            $table->integer('story_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2c66c4278f8c72e86dd37b5f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_story_fwd_header_story_fwd_header');
        Schema::dropIfExists('tl_story_fwd_header');
    }
};
