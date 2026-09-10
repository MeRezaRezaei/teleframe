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
        Schema::create('tl_todo_list_todo_list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('others_can_append')->default(false);
            $table->boolean('others_can_complete')->default(false);
            $table->bigInteger('title')->nullable();
            $table->index('title', 'ix_05bbf46d093826599e65b21e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_499a3851f4ca97dcc33c7fe2');
            $table->index('account_id', 'ix_495b83ac94f3cc4b8697bcff');
        });
        Schema::create('tl_todo_list_todo_list__list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_8db6c133a575649e55cd09da')->references('id')->on('tl_todo_list_todo_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_817181f16c676f96cd63');
            $table->index('account_id', 'ix_b1dbb18534149bb9af484f38');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_todo_list_todo_list__list');
        Schema::dropIfExists('tl_todo_list_todo_list');
    }
};
