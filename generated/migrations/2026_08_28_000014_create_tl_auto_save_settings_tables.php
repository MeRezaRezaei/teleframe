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
        Schema::create('tl_auto_save_settings_auto_save_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('photos')->default(false);
            $table->boolean('videos')->default(false);
            $table->bigInteger('video_max_size')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_450a2a143b39c2f81fecd43b');
            $table->index('account_id', 'ix_c44d371fc5caaaeb889e8831');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auto_save_settings_auto_save_settings');
    }
};
