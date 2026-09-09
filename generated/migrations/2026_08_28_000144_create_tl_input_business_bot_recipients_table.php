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
            $table->index('constructor_id', 'ix_a5b4e7527742d796c704e859');
            $table->index('account_id', 'ix_d7812a83b8d5488fa7cb52c6');
        });
        Schema::create('tl_input_business_bot_recipients_input_busine_36d9b3380d51', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_business_bot_recipients')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('existing_chats')->default(false);
            $table->boolean('new_chats')->default(false);
            $table->boolean('contacts')->default(false);
            $table->boolean('non_contacts')->default(false);
            $table->boolean('exclude_selected')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f37dca9d1a726122ff3e1f7a');
        });
        Schema::create('tl_input_business_bot_recipients_input_busine_bff2da7dc05c', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_business_bot_recipients_input_busine_36d9b3380d51')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0b1ce492335179eeed0b');
            $table->index('account_id', 'ix_c72fe4cc4aec04c2a2140631');
        });
        Schema::create('tl_input_business_bot_recipients_input_busine_6c436ddfb4f9', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_business_bot_recipients_input_busine_36d9b3380d51')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bf52eaa4f91c3cf0aeb5');
            $table->index('account_id', 'ix_36b956174624b5ac8d52766e');
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
