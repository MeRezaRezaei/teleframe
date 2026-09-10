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
        Schema::create('tl_wall_paper_settings_wall_paper_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('blur')->default(false);
            $table->boolean('motion')->default(false);
            $table->integer('background_color')->nullable();
            $table->integer('second_background_color')->nullable();
            $table->integer('third_background_color')->nullable();
            $table->integer('fourth_background_color')->nullable();
            $table->integer('intensity')->nullable();
            $table->integer('rotation')->nullable();
            $table->text('emoticon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d74c94f6ae13b4480e5595de');
            $table->index('account_id', 'ix_e766285b55c02f7cf8b8b219');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_wall_paper_settings_wall_paper_settings');
    }
};
