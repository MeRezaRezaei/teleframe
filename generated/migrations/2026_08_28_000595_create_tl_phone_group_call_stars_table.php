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
        Schema::create('tl_phone_group_call_stars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_phone_group_call_stars_group_call_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_group_call_stars')->cascadeOnDelete();
            $table->bigInteger('total_stars');
            $table->timestamps();
        });
        Schema::create('tl_phone_group_call_stars_group_call_stars__top_donors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_group_call_stars_group_call_stars')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4a665a89042cfc2c1fc3');
        });
        Schema::create('tl_phone_group_call_stars_group_call_stars__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_group_call_stars_group_call_stars')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f8ba20ba8942fc8c645b');
        });
        Schema::create('tl_phone_group_call_stars_group_call_stars__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_group_call_stars_group_call_stars')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f1b7db4ab6677cce679f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_group_call_stars_group_call_stars__users');
        Schema::dropIfExists('tl_phone_group_call_stars_group_call_stars__chats');
        Schema::dropIfExists('tl_phone_group_call_stars_group_call_stars__top_donors');
        Schema::dropIfExists('tl_phone_group_call_stars_group_call_stars');
        Schema::dropIfExists('tl_phone_group_call_stars');
    }
};
