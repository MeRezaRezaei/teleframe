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
        Schema::create('tl_business_chat_link', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_0100aa4daa950764b32265d7');
            $table->index('account_id', 'ix_a9e9da1123796c38a967c4d2');
        });
        Schema::create('tl_business_chat_link_business_chat_link', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_chat_link')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('link');
            $table->text('message');
            $table->text('title')->nullable();
            $table->integer('views');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b92e382d8d7fc8cba2ce4a7e');
        });
        Schema::create('tl_business_chat_link_business_chat_link__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_business_chat_link_business_chat_link')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ebb2506dcccff0048244');
            $table->index('account_id', 'ix_0b5260888171ef8bd856af01');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_chat_link_business_chat_link__entities');
        Schema::dropIfExists('tl_business_chat_link_business_chat_link');
        Schema::dropIfExists('tl_business_chat_link');
    }
};
