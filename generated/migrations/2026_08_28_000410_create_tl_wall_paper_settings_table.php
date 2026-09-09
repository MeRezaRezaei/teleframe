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
        Schema::create('tl_wall_paper_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_611534b21827e1c981fc9a3f');
            $table->index('account_id', 'ix_fcf1cc9a6a03e9c3a8afdf83');
        });
        Schema::create('tl_wall_paper_settings_wall_paper_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_wall_paper_settings')->cascadeOnDelete();
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
            $table->index('account_id', 'ix_e766285b55c02f7cf8b8b219');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_wall_paper_settings_wall_paper_settings');
        Schema::dropIfExists('tl_wall_paper_settings');
    }
};
