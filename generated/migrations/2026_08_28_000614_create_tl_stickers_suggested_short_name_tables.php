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
        Schema::create('tl_stickers_suggested_short_name_suggested_short_name', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('short_name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1c73679f0548ea7d34ad97a4');
            $table->index('account_id', 'ix_d125f7ed12a199053580eca3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stickers_suggested_short_name_suggested_short_name');
    }
};
