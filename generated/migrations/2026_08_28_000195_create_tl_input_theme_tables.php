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
        Schema::create('tl_input_theme_input_theme', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dceb25bfe325419dee1b66f5');
            $table->index('account_id', 'ix_006f6497023a93cca3893eb1');
        });
        Schema::create('tl_input_theme_input_theme_slug', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('slug')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d197c7e6e0fcd7e3baffdcca');
            $table->index('account_id', 'ix_2fb7da8223faf0f82de31068');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_theme_input_theme_slug');
        Schema::dropIfExists('tl_input_theme_input_theme');
    }
};
