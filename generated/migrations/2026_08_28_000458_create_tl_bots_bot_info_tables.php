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
        Schema::create('tl_bots_bot_info_bot_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('name')->nullable();
            $table->text('about')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e8cb384f455998c7e2f29985');
            $table->index('account_id', 'ix_6d98c744beabe38612abfcf1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bots_bot_info_bot_info');
    }
};
