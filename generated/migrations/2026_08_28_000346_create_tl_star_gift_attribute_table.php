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
        Schema::create('tl_star_gift_attribute', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_star_gift_attribute_star_gift_attribute_backdrop', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute')->cascadeOnDelete();
            $table->text('name');
            $table->integer('backdrop_id');
            $table->integer('center_color');
            $table->integer('edge_color');
            $table->integer('pattern_color');
            $table->integer('text_color');
            $table->uuid('rarity');
            $table->timestamps();
        });
        Schema::create('tl_star_gift_attribute_star_gift_attribute_model', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('crafted')->default(false);
            $table->text('name');
            $table->uuid('document');
            $table->uuid('rarity');
            $table->timestamps();
        });
        Schema::create('tl_star_gift_attribute_star_gift_attribute_or_06bf30da1b7d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('sender_id')->nullable();
            $table->uuid('recipient_id');
            $table->integer('date');
            $table->uuid('message')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_star_gift_attribute_star_gift_attribute_pattern', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute')->cascadeOnDelete();
            $table->text('name');
            $table->uuid('document');
            $table->uuid('rarity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_attribute_star_gift_attribute_pattern');
        Schema::dropIfExists('tl_star_gift_attribute_star_gift_attribute_or_06bf30da1b7d');
        Schema::dropIfExists('tl_star_gift_attribute_star_gift_attribute_model');
        Schema::dropIfExists('tl_star_gift_attribute_star_gift_attribute_backdrop');
        Schema::dropIfExists('tl_star_gift_attribute');
    }
};
