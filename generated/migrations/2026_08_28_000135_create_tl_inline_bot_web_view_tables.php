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
        Schema::create('tl_inline_bot_web_view_inline_bot_web_view', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('text')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1ea41d54c2df52332c81ca42');
            $table->index('account_id', 'ix_b167356ed62b196b47e84b4f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_inline_bot_web_view_inline_bot_web_view');
    }
};
