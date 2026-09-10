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
        Schema::create('tl_input_wall_paper_input_wall_paper', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fb8ea5e5ddf35eeb7ed332be');
            $table->index('account_id', 'ix_bcd4ad35addd73e60215a2fe');
        });
        Schema::create('tl_input_wall_paper_input_wall_paper_no_file', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_af1e11febb0e914be1c0d6ff');
            $table->index('account_id', 'ix_6073d9f4eb92c8b1c7eaffe5');
        });
        Schema::create('tl_input_wall_paper_input_wall_paper_slug', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('slug')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_01226fed1b96878263f21b70');
            $table->index('account_id', 'ix_e267caf930457d3e48130d6a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_wall_paper_input_wall_paper_slug');
        Schema::dropIfExists('tl_input_wall_paper_input_wall_paper_no_file');
        Schema::dropIfExists('tl_input_wall_paper_input_wall_paper');
    }
};
