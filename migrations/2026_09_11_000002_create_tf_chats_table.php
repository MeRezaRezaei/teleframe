<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_chats', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->bigInteger('account_id');
            $table->text('title')->nullable();
            $table->integer('participants_count')->nullable();
            $table->integer('version')->nullable();
            $table->integer('date')->nullable();
            $table->boolean('is_deactivated')->default(false);
            $table->boolean('is_left')->default(false);
            $table->boolean('is_creator')->default(false);
            $table->boolean('is_call_active')->default(false);
            $table->boolean('is_noforwards')->default(false);
            $table->jsonb('tl_data');
            $table->timestamps();

            $table->primary(['id', 'account_id']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_chats');
    }
};
