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
        Schema::create('tl_star_gift_background_star_gift_background', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('center_color')->nullable();
            $table->integer('edge_color')->nullable();
            $table->integer('text_color')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2224dde1fdffd49e727f76f7');
            $table->index('account_id', 'ix_eba9d7ce584159eaea19499e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_background_star_gift_background');
    }
};
