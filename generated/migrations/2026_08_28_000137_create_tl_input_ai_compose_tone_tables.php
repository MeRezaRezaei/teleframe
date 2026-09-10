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
        Schema::create('tl_input_ai_compose_tone_input_ai_compose_tone_default', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tone')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d3a53003f297de0dc979c334');
            $table->index('account_id', 'ix_0eec3c481a2226c421c50641');
        });
        Schema::create('tl_input_ai_compose_tone_input_ai_compose_tone_i_d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_134876b33692d9d7a24f7c7d');
            $table->index('account_id', 'ix_196d2901d575f1e2bc01213e');
        });
        Schema::create('tl_input_ai_compose_tone_input_ai_compose_tone_slug', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('slug')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3bce972e04d99dd489eac080');
            $table->index('account_id', 'ix_a5b846095c44e3175aaa3903');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_ai_compose_tone_input_ai_compose_tone_slug');
        Schema::dropIfExists('tl_input_ai_compose_tone_input_ai_compose_tone_i_d');
        Schema::dropIfExists('tl_input_ai_compose_tone_input_ai_compose_tone_default');
    }
};
