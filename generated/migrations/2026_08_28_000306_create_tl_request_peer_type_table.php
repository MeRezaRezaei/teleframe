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
        Schema::create('tl_request_peer_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_request_peer_type_request_peer_type_broadcast', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_request_peer_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->uuid('has_username')->nullable();
            $table->uuid('user_admin_rights')->nullable();
            $table->uuid('bot_admin_rights')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_request_peer_type_request_peer_type_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_request_peer_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->boolean('bot_participant')->default(false);
            $table->uuid('has_username')->nullable();
            $table->uuid('forum')->nullable();
            $table->uuid('user_admin_rights')->nullable();
            $table->uuid('bot_admin_rights')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_request_peer_type_request_peer_type_create_bot', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_request_peer_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('bot_managed')->default(false);
            $table->text('suggested_name')->nullable();
            $table->text('suggested_username')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_request_peer_type_request_peer_type_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_request_peer_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('bot')->nullable();
            $table->uuid('premium')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_request_peer_type_request_peer_type_user');
        Schema::dropIfExists('tl_request_peer_type_request_peer_type_create_bot');
        Schema::dropIfExists('tl_request_peer_type_request_peer_type_chat');
        Schema::dropIfExists('tl_request_peer_type_request_peer_type_broadcast');
        Schema::dropIfExists('tl_request_peer_type');
    }
};
