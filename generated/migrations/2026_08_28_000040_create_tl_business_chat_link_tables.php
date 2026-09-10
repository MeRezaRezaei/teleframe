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
        Schema::create('tl_business_chat_link_business_chat_link', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('link')->nullable();
            $table->text('message')->nullable();
            $table->text('title')->nullable();
            $table->integer('views')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c66b3af6eb299f1f3bc6ad2c');
            $table->index('account_id', 'ix_b92e382d8d7fc8cba2ce4a7e');
        });
        Schema::create('tl_business_chat_link_business_chat_link__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_f42c231aad0862668147facb')->references('id')->on('tl_business_chat_link_business_chat_link')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ebb2506dcccff0048244');
            $table->index('account_id', 'ix_0b5260888171ef8bd856af01');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_chat_link_business_chat_link__entities');
        Schema::dropIfExists('tl_business_chat_link_business_chat_link');
    }
};
