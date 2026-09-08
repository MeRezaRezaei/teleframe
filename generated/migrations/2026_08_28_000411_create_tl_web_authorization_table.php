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
        Schema::create('tl_web_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_web_authorization_web_authorization', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_authorization')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('bot_id');
            $table->text('domain');
            $table->text('browser');
            $table->text('platform');
            $table->integer('date_created');
            $table->integer('date_active');
            $table->text('ip');
            $table->text('region');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_authorization_web_authorization');
        Schema::dropIfExists('tl_web_authorization');
    }
};
