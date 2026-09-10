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
        Schema::create('tl_quick_reply_quick_reply', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('shortcut_id')->nullable();
            $table->text('shortcut')->nullable();
            $table->integer('top_message')->nullable();
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_995f8ae2209984f15e9b391d');
            $table->index('account_id', 'ix_9baba98cac3b71b236c5faf3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_quick_reply_quick_reply');
    }
};
