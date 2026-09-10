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
        Schema::create('tl_game_game', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->text('short_name')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_348d11d83f5faff82b5f8df2');
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_f55086c5b76a6e8be3518f89');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_cc72e322b1ed1bd0068ea424');
            $table->index('account_id', 'ix_6b572ca76dacba2f6fcb074a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_game_game');
    }
};
