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
        Schema::create('tl_story_fwd_header_story_fwd_header', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('modified')->default(false);
            $table->bigInteger('tl_from')->nullable();
            $table->index('tl_from', 'ix_1d72871e445adc070163e656');
            $table->text('from_name')->nullable();
            $table->integer('story_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4ad35cb9e8a10fba70c329af');
            $table->index('account_id', 'ix_2c66c4278f8c72e86dd37b5f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_story_fwd_header_story_fwd_header');
    }
};
