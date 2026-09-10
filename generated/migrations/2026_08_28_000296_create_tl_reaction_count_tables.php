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
        Schema::create('tl_reaction_count_reaction_count', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('chosen_order')->nullable();
            $table->bigInteger('reaction')->nullable();
            $table->index('reaction', 'ix_e7ab869705a2510c625fd030');
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ae8cf0ca78f274a17c3d0517');
            $table->index('account_id', 'ix_87383d05f0efa354f6955829');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_reaction_count_reaction_count');
    }
};
