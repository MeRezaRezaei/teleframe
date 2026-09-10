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
        Schema::create('tl_input_business_bot_recipients_input_busine_36d9b3380d51', function (Blueprint $table) {
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
            $table->index('constructor_id', 'ix_982844cf8611dd86c4d5ffc5');
            $table->index('account_id', 'ix_f37dca9d1a726122ff3e1f7a');
        });
        Schema::create('tl_input_business_bot_recipients_input_busine_bff2da7dc05c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_e1311071b8a70b27266061c3')->references('id')->on('tl_input_business_bot_recipients_input_busine_36d9b3380d51')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0b1ce492335179eeed0b');
            $table->index('account_id', 'ix_c72fe4cc4aec04c2a2140631');
        });
        Schema::create('tl_input_business_bot_recipients_input_busine_6c436ddfb4f9', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_ee97ac8b09ee3a7a56364dc9')->references('id')->on('tl_input_business_bot_recipients_input_busine_36d9b3380d51')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
