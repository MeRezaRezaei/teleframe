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
        Schema::create('tl_exported_chat_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_exported_chat_invite_chat_invite_exported', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_exported_chat_invite')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('revoked')->default(false);
            $table->boolean('permanent')->default(false);
            $table->boolean('request_needed')->default(false);
            $table->text('link');
            $table->bigInteger('admin_id');
            $table->integer('date');
            $table->integer('start_date')->nullable();
            $table->integer('expire_date')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage')->nullable();
            $table->integer('requested')->nullable();
            $table->integer('subscription_expired')->nullable();
            $table->text('title')->nullable();
            $table->uuid('subscription_pricing')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_exported_chat_invite_chat_invite_public_join_requests', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_exported_chat_invite')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_exported_chat_invite_chat_invite_public_join_requests');
        Schema::dropIfExists('tl_exported_chat_invite_chat_invite_exported');
        Schema::dropIfExists('tl_exported_chat_invite');
    }
};
