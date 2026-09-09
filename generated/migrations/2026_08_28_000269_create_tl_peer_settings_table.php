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
        Schema::create('tl_peer_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4d68b0b29bfed40909ca0459');
            $table->index('account_id', 'ix_38cddb6f6368fe64adfbfc13');
        });
        Schema::create('tl_peer_settings_peer_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('report_spam')->default(false);
            $table->boolean('add_contact')->default(false);
            $table->boolean('block_contact')->default(false);
            $table->boolean('share_contact')->default(false);
            $table->boolean('need_contacts_exception')->default(false);
            $table->boolean('report_geo')->default(false);
            $table->boolean('autoarchived')->default(false);
            $table->boolean('invite_members')->default(false);
            $table->boolean('request_chat_broadcast')->default(false);
            $table->boolean('business_bot_paused')->default(false);
            $table->boolean('business_bot_can_reply')->default(false);
            $table->integer('geo_distance')->nullable();
            $table->text('request_chat_title')->nullable();
            $table->integer('request_chat_date')->nullable();
            $table->bigInteger('business_bot_id')->nullable();
            $table->index('business_bot_id', 'ix_b1aa97393efbb34095263755');
            $table->text('business_bot_manage_url')->nullable();
            $table->bigInteger('charge_paid_message_stars')->nullable();
            $table->text('registration_month')->nullable();
            $table->text('phone_country')->nullable();
            $table->integer('name_change_date')->nullable();
            $table->integer('photo_change_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_33db9926cbfd82f8e5111236');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_settings_peer_settings');
        Schema::dropIfExists('tl_peer_settings');
    }
};
