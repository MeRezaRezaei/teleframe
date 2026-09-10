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
        Schema::create('tl_base_theme_base_theme_arctic', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4c8b09d173db514df2ba22a8');
            $table->index('account_id', 'ix_a014eaea5e9dec009a7977f9');
        });
        Schema::create('tl_base_theme_base_theme_classic', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7acbdcb023c9fb8780ebaf4d');
            $table->index('account_id', 'ix_b6d0e46c0ba2ea981b5352b2');
        });
        Schema::create('tl_base_theme_base_theme_day', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_71c9d0b9c9d8e7c6c7f02f18');
            $table->index('account_id', 'ix_37a09689e63b74f6b53fbe38');
        });
        Schema::create('tl_base_theme_base_theme_night', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_138755ba290d687d3b2c79ce');
            $table->index('account_id', 'ix_a17ec9aa1d0cbb53e3a9c0b5');
        });
        Schema::create('tl_base_theme_base_theme_tinted', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9f9f667878b623ebc8eda739');
            $table->index('account_id', 'ix_64838f72237ba8bfc1767345');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_base_theme_base_theme_tinted');
        Schema::dropIfExists('tl_base_theme_base_theme_night');
        Schema::dropIfExists('tl_base_theme_base_theme_day');
        Schema::dropIfExists('tl_base_theme_base_theme_classic');
        Schema::dropIfExists('tl_base_theme_base_theme_arctic');
    }
};
