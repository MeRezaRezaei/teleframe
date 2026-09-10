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
        Schema::create('tl_http_wait_http_wait', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('max_delay')->nullable();
            $table->integer('wait_after')->nullable();
            $table->integer('max_wait')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bad2dafe84a919d0bc2931aa');
            $table->index('account_id', 'ix_2fba1a9bdf8eeecf4a41de87');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_http_wait_http_wait');
    }
};
