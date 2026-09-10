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
        Schema::create('tl_ai_compose_tone_example_ai_compose_tone_example', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_from')->nullable();
            $table->index('tl_from', 'ix_04e2790b707c94158f93c119');
            $table->bigInteger('tl_to')->nullable();
            $table->index('tl_to', 'ix_5863da33347623134ffc5996');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2c8ae5cafa0fc2f1fb5f91b6');
            $table->index('account_id', 'ix_a37e8e8b21540e0710ca2d93');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_ai_compose_tone_example_ai_compose_tone_example');
    }
};
