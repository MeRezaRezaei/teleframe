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
        Schema::create('tl_todo_completion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_fbe07fb7347026ac831a31df');
            $table->index('account_id', 'ix_cd75795a62c8868e68be1e3d');
        });
        Schema::create('tl_todo_completion_todo_completion', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_todo_completion')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('completed_by');
            $table->index('completed_by', 'ix_804a3b3ddb49bab12bcfbda7');
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8e820e5df2609dff55da6d78');
            $table->unique(['account_id', 'completed_by', 'tl_id'], 'ux_ab381df05a37b4f75038');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_todo_completion_todo_completion');
        Schema::dropIfExists('tl_todo_completion');
    }
};
