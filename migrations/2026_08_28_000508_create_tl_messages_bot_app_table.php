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
        Schema::create('tl_messages_bot_app', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8b6899378828d6e92ec762c2');
            $table->index('account_id', 'ix_0ed6e4138ce31c4258e70b81');
        });
        Schema::create('tl_messages_bot_app_bot_app', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_bot_app')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('inactive')->default(false);
            $table->boolean('request_write_access')->default(false);
            $table->boolean('has_settings')->default(false);
            $table->uuid('app');
            $table->index('app', 'ix_289205534c4658317b9430ab');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2bda2a6fd31cba65cc3b56c5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_bot_app_bot_app');
        Schema::dropIfExists('tl_messages_bot_app');
    }
};
