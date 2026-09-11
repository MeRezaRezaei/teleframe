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
        Schema::create('tf_chats', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->bigInteger('constructor_id');
            $table->bigInteger('account_id');
            $table->text('title')->nullable();
            $table->integer('participants_count')->nullable();
            $table->integer('version')->nullable();
            $table->integer('date')->nullable();
            $table->boolean('is_deactivated')->default(false);
            $table->boolean('is_left')->default(false);
            $table->jsonb('tl_data');
            $table->timestamps();
            $table->primary(['id', 'account_id']);
            $table->index('account_id', 'ix_tf_chats_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_chats');
    }
};
