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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_saved_star_gift_input_saved_star_gift_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_saved_star_gift')->cascadeOnDelete();
            $table->uuid('peer');
            $table->bigInteger('saved_id');
            $table->timestamps();
        });
        Schema::create('tl_input_saved_star_gift_input_saved_star_gift_slug', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_saved_star_gift')->cascadeOnDelete();
            $table->text('slug');
            $table->timestamps();
        });
        Schema::create('tl_input_saved_star_gift_input_saved_star_gift_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_saved_star_gift')->cascadeOnDelete();
            $table->integer('msg_id');
            $table->timestamps();
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
