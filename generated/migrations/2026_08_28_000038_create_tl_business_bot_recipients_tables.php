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
        Schema::create('tl_business_bot_recipients_business_bot_recipients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('existing_chats')->default(false);
            $table->boolean('new_chats')->default(false);
            $table->boolean('contacts')->default(false);
            $table->boolean('non_contacts')->default(false);
            $table->boolean('exclude_selected')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6a8b86b7a314667be72c8eb0');
            $table->index('account_id', 'ix_de8e9c1f293ec7b1f7460b26');
        });
        Schema::create('tl_business_bot_recipients_business_bot_recipients__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_business_bot_recipients_business_bot_recipients', 'id', 'fk_aa46f376beb4c6d101db0680')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_89f26ea95f68b4c9d832');
            $table->index('account_id', 'ix_95989a5ee1a6cc0ac6821091');
        });
        Schema::create('tl_business_bot_recipients_business_bot_recip_67c6d576f447', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_business_bot_recipients_business_bot_recipients', 'id', 'fk_4336a51f48c028550eda653f')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_85a3c1a457de8302fefb');
            $table->index('account_id', 'ix_46f6ff34fe5c2789d587a224');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_bot_recipients_business_bot_recip_67c6d576f447');
        Schema::dropIfExists('tl_business_bot_recipients_business_bot_recipients__users');
        Schema::dropIfExists('tl_business_bot_recipients_business_bot_recipients');
    }
};
