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
        Schema::create('tl_input_saved_star_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b584faad48786fb66b0ad63f');
            $table->index('account_id', 'ix_4ce3a787353b1bdd8b6c2f3d');
        });
        Schema::create('tl_input_saved_star_gift_input_saved_star_gift_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_saved_star_gift')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_115dca6ca050657077495f0a');
            $table->bigInteger('saved_id');
            $table->index('saved_id', 'ix_cffde22084ea25dafe7d0e02');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ae92762d04055a3da707797c');
        });
        Schema::create('tl_input_saved_star_gift_input_saved_star_gift_slug', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_saved_star_gift')->cascadeOnDelete();
            $table->text('slug');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b736e28ed74b82b440b99d11');
        });
        Schema::create('tl_input_saved_star_gift_input_saved_star_gift_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_saved_star_gift')->cascadeOnDelete();
            $table->integer('msg_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e1d37edda9958eb9b2e7108a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_saved_star_gift_input_saved_star_gift_user');
        Schema::dropIfExists('tl_input_saved_star_gift_input_saved_star_gift_slug');
        Schema::dropIfExists('tl_input_saved_star_gift_input_saved_star_gift_chat');
        Schema::dropIfExists('tl_input_saved_star_gift');
    }
};
