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
        Schema::create('tl_suggested_post', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9182f96db50ea33286d26ef6');
            $table->index('account_id', 'ix_574b81aa9d0a7e606a24e63f');
        });
        Schema::create('tl_suggested_post_suggested_post', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_suggested_post')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('accepted')->default(false);
            $table->boolean('rejected')->default(false);
            $table->uuid('price')->nullable();
            $table->index('price', 'ix_8a3e4d2a236381399f99d5bf');
            $table->integer('schedule_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_601f97587ad4bf8757f22fb8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_suggested_post_suggested_post');
        Schema::dropIfExists('tl_suggested_post');
    }
};
