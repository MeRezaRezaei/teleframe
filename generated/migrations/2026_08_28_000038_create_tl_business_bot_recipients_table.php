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
        Schema::create('tl_business_bot_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_business_bot_recipients_business_bot_recipients', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_bot_recipients')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('existing_chats')->default(false);
            $table->boolean('new_chats')->default(false);
            $table->boolean('contacts')->default(false);
            $table->boolean('non_contacts')->default(false);
            $table->boolean('exclude_selected')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_business_bot_recipients_business_bot_recipients__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_business_bot_recipients_business_bot_recipients')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_89f26ea95f68b4c9d832');
        });
        Schema::create('tl_business_bot_recipients_business_bot_recip_67c6d576f447', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_business_bot_recipients_business_bot_recipients')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_85a3c1a457de8302fefb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_bot_recipients_business_bot_recip_67c6d576f447');
        Schema::dropIfExists('tl_business_bot_recipients_business_bot_recipients__users');
        Schema::dropIfExists('tl_business_bot_recipients_business_bot_recipients');
        Schema::dropIfExists('tl_business_bot_recipients');
    }
};
