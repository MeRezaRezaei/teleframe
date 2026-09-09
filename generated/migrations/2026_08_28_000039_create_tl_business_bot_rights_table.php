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
        Schema::create('tl_business_bot_rights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8b5c41fd02044e01d3d313c2');
            $table->index('account_id', 'ix_a779406832ac5c8be38d5591');
        });
        Schema::create('tl_business_bot_rights_business_bot_rights', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_bot_rights')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('reply')->default(false);
            $table->boolean('read_messages')->default(false);
            $table->boolean('delete_sent_messages')->default(false);
            $table->boolean('delete_received_messages')->default(false);
            $table->boolean('edit_name')->default(false);
            $table->boolean('edit_bio')->default(false);
            $table->boolean('edit_profile_photo')->default(false);
            $table->boolean('edit_username')->default(false);
            $table->boolean('view_gifts')->default(false);
            $table->boolean('sell_gifts')->default(false);
            $table->boolean('change_gift_settings')->default(false);
            $table->boolean('transfer_and_upgrade_gifts')->default(false);
            $table->boolean('transfer_stars')->default(false);
            $table->boolean('manage_stories')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_747ea32cd044be94f1757154');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_bot_rights_business_bot_rights');
        Schema::dropIfExists('tl_business_bot_rights');
    }
};
