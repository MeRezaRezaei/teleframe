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
        Schema::create('tl_todo_completion_todo_completion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_id')->nullable();
            $table->bigInteger('completed_by')->nullable();
            $table->index('completed_by', 'ix_804a3b3ddb49bab12bcfbda7');
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_518e15750d01734e547d4591');
            $table->index('account_id', 'ix_8e820e5df2609dff55da6d78');
            $table->unique(['completed_by', 'account_id'], 'ux_ab381df05a37b4f75038');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_todo_completion_todo_completion');
    }
};
