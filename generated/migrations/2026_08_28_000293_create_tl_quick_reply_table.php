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
        Schema::create('tl_quick_reply', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_72b9e84dd70e390c2a20bd72');
            $table->index('account_id', 'ix_b236d35e8755cdf18d111216');
        });
        Schema::create('tl_quick_reply_quick_reply', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_quick_reply')->cascadeOnDelete();
            $table->integer('shortcut_id');
            $table->text('shortcut');
            $table->integer('top_message');
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9baba98cac3b71b236c5faf3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_quick_reply_quick_reply');
        Schema::dropIfExists('tl_quick_reply');
    }
};
