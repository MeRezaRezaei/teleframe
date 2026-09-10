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
        Schema::create('tl_available_reaction_available_reaction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('inactive')->default(false);
            $table->boolean('premium')->default(false);
            $table->text('reaction')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('static_icon')->nullable();
            $table->index('static_icon', 'ix_f8f1bac7cc1db463be5a2d59');
            $table->bigInteger('appear_animation')->nullable();
            $table->index('appear_animation', 'ix_48e5b436f51911d7c0a224b0');
            $table->bigInteger('select_animation')->nullable();
            $table->index('select_animation', 'ix_fcde867df384833daaf76072');
            $table->bigInteger('activate_animation')->nullable();
            $table->index('activate_animation', 'ix_6cc2af4193d0e3ff4323170d');
            $table->bigInteger('effect_animation')->nullable();
            $table->index('effect_animation', 'ix_97f67f503ebe0f89fc5d928d');
            $table->bigInteger('around_animation')->nullable();
            $table->index('around_animation', 'ix_e9484bf8fa0b131068d53338');
            $table->bigInteger('center_icon')->nullable();
            $table->index('center_icon', 'ix_f99225758e88d58c80a7f928');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_85b1bd735b83b12abbd3144f');
            $table->index('account_id', 'ix_2eb71b30b0bdbc34650d9cca');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_available_reaction_available_reaction');
    }
};
