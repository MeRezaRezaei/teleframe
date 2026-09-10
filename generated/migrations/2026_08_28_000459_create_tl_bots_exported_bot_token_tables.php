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
        Schema::create('tl_bots_exported_bot_token_exported_bot_token', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('token')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7cc6d6f369de3702f980b4c3');
            $table->index('account_id', 'ix_a41b64f2d400e4c794e845ff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bots_exported_bot_token_exported_bot_token');
    }
};
