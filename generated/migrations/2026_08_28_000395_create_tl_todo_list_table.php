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
        Schema::create('tl_todo_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e5fa8cc24e9b2a55b6d5e7f4');
            $table->index('account_id', 'ix_7b4b715841123f9cd02c1219');
        });
        Schema::create('tl_todo_list_todo_list', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_todo_list')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('others_can_append')->default(false);
            $table->boolean('others_can_complete')->default(false);
            $table->uuid('title');
            $table->index('title', 'ix_05bbf46d093826599e65b21e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_495b83ac94f3cc4b8697bcff');
        });
        Schema::create('tl_todo_list_todo_list__list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_todo_list_todo_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_817181f16c676f96cd63');
            $table->index('account_id', 'ix_b1dbb18534149bb9af484f38');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_todo_list_todo_list__list');
        Schema::dropIfExists('tl_todo_list_todo_list');
        Schema::dropIfExists('tl_todo_list');
    }
};
