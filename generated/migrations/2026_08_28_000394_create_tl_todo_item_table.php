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
        Schema::create('tl_todo_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9d132c05f4beaaa172677a03');
            $table->index('account_id', 'ix_7cb41e78e2a4ac66d0e80dfc');
        });
        Schema::create('tl_todo_item_todo_item', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_todo_item')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->uuid('title');
            $table->index('title', 'ix_84fca01a760428badcf9ca1c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_af1e6d1c18714bc2fd0c1d5c');
            $table->unique(['account_id', 'tl_id'], 'ux_09855728c3b42578cad4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_todo_item_todo_item');
        Schema::dropIfExists('tl_todo_item');
    }
};
