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
        Schema::create('tl_bool_bool_false', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_49481db84cc8cbe262a5df6c');
            $table->index('account_id', 'ix_c6a87c0adeb7922151f979ad');
        });
        Schema::create('tl_bool_bool_true', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_66e48b4ed1f8d50cc4966ca3');
            $table->index('account_id', 'ix_3cd289d08e7260406cfac508');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bool_bool_true');
        Schema::dropIfExists('tl_bool_bool_false');
    }
};
