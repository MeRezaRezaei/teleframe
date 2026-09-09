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
        Schema::create('tl_available_reaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_927450a83f65e1b28a037224');
            $table->index('account_id', 'ix_ad8d61d6fe264314f0fc84ad');
        });
        Schema::create('tl_available_reaction_available_reaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_available_reaction')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('inactive')->default(false);
            $table->boolean('premium')->default(false);
            $table->text('reaction');
            $table->text('title');
            $table->uuid('static_icon');
            $table->index('static_icon', 'ix_f8f1bac7cc1db463be5a2d59');
            $table->uuid('appear_animation');
            $table->index('appear_animation', 'ix_48e5b436f51911d7c0a224b0');
            $table->uuid('select_animation');
            $table->index('select_animation', 'ix_fcde867df384833daaf76072');
            $table->uuid('activate_animation');
            $table->index('activate_animation', 'ix_6cc2af4193d0e3ff4323170d');
            $table->uuid('effect_animation');
            $table->index('effect_animation', 'ix_97f67f503ebe0f89fc5d928d');
            $table->uuid('around_animation')->nullable();
            $table->index('around_animation', 'ix_e9484bf8fa0b131068d53338');
            $table->uuid('center_icon')->nullable();
            $table->index('center_icon', 'ix_f99225758e88d58c80a7f928');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2eb71b30b0bdbc34650d9cca');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_available_reaction_available_reaction');
        Schema::dropIfExists('tl_available_reaction');
    }
};
