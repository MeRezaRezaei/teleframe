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
        Schema::create('tl_messages_bot_app_bot_app', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('inactive')->default(false);
            $table->boolean('request_write_access')->default(false);
            $table->boolean('has_settings')->default(false);
            $table->bigInteger('app')->nullable();
            $table->index('app', 'ix_289205534c4658317b9430ab');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f956e3cc7a30fb220979d1ec');
            $table->index('account_id', 'ix_2bda2a6fd31cba65cc3b56c5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_bot_app_bot_app');
    }
};
