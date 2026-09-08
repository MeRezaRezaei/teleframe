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
        Schema::create('tl_keyboard_button', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_keyboard_button_input_keyboard_button_request_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('name_requested')->default(false);
            $table->boolean('username_requested')->default(false);
            $table->boolean('photo_requested')->default(false);
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->integer('button_id');
            $table->uuid('peer_type');
            $table->integer('max_quantity');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_input_keyboard_button_url_auth', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('request_write_access')->default(false);
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->text('fwd_text')->nullable();
            $table->text('url');
            $table->uuid('bot');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_input_keyboard_button_user_profile', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->uuid('user_id');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_buy', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_callback', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('requires_password')->default(false);
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->binary('data');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_copy', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->text('copy_text');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_game', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_request_geo_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_request_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->integer('button_id');
            $table->uuid('peer_type');
            $table->integer('max_quantity');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_request_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_request_poll', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->uuid('quiz')->nullable();
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_simple_web_view', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->text('url');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_switch_inline', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('same_peer')->default(false);
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->text('query');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_switch_inl_24451aa92e03', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_keyboard_button_keyboard_button_switch_inline')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8ffc7de80710e9fe5f02');
        });
        Schema::create('tl_keyboard_button_keyboard_button_url', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->text('url');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_url_auth', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->text('fwd_text')->nullable();
            $table->text('url');
            $table->integer('button_id');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_user_profile', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->bigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('tl_keyboard_button_keyboard_button_web_view', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('style')->nullable();
            $table->text('text');
            $table->text('url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_web_view');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_user_profile');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_url_auth');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_url');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_switch_inl_24451aa92e03');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_switch_inline');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_simple_web_view');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_request_poll');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_request_phone');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_request_peer');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_request_geo_location');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_game');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_copy');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_callback');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_buy');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button');
        Schema::dropIfExists('tl_keyboard_button_input_keyboard_button_user_profile');
        Schema::dropIfExists('tl_keyboard_button_input_keyboard_button_url_auth');
        Schema::dropIfExists('tl_keyboard_button_input_keyboard_button_request_peer');
        Schema::dropIfExists('tl_keyboard_button');
    }
};
