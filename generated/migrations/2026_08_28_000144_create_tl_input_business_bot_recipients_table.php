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
        Schema::create('tl_input_business_bot_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_business_bot_recipients_input_busine_36d9b3380d51', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_business_bot_recipients')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('existing_chats')->default(false);
            $table->boolean('new_chats')->default(false);
            $table->boolean('contacts')->default(false);
            $table->boolean('non_contacts')->default(false);
            $table->boolean('exclude_selected')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_input_business_bot_recipients_input_busine_bff2da7dc05c', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_business_bot_recipients_input_busine_36d9b3380d51')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0b1ce492335179eeed0b');
        });
        Schema::create('tl_input_business_bot_recipients_input_busine_6c436ddfb4f9', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_business_bot_recipients_input_busine_36d9b3380d51')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_bf52eaa4f91c3cf0aeb5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_business_bot_recipients_input_busine_6c436ddfb4f9');
        Schema::dropIfExists('tl_input_business_bot_recipients_input_busine_bff2da7dc05c');
        Schema::dropIfExists('tl_input_business_bot_recipients_input_busine_36d9b3380d51');
        Schema::dropIfExists('tl_input_business_bot_recipients');
    }
};
