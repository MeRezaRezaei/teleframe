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
        Schema::create('tl_todo_item_todo_item', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_id')->nullable();
            $table->bigInteger('title')->nullable();
            $table->index('title', 'ix_84fca01a760428badcf9ca1c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3add428f6751fdc0e94a15b2');
            $table->index('account_id', 'ix_af1e6d1c18714bc2fd0c1d5c');
            $table->unique(['account_id'], 'ux_09855728c3b42578cad4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_todo_item_todo_item');
    }
};
