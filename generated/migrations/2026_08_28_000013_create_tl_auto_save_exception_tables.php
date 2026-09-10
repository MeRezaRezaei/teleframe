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
        Schema::create('tl_auto_save_exception_auto_save_exception', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_be07fd4b73c2283e951be324');
            $table->bigInteger('settings')->nullable();
            $table->index('settings', 'ix_1e25d44f4b693cfeb0de4319');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5cd14ffe45c0bf97c8180bb9');
            $table->index('account_id', 'ix_c48686cafcb72ca9d37aa44c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auto_save_exception_auto_save_exception');
    }
};
