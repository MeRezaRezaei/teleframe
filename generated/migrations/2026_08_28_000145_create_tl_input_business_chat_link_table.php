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
        Schema::create('tl_input_business_chat_link', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_71a3845afc27b6b5aea2b9ff');
            $table->index('account_id', 'ix_138aaee83bce1ff5b8276e3f');
        });
        Schema::create('tl_input_business_chat_link_input_business_chat_link', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_business_chat_link')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('message');
            $table->text('title')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f5a4d10d2697f47d00f80d9e');
        });
        Schema::create('tl_input_business_chat_link_input_business_ch_2c7a8d471020', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_business_chat_link_input_business_chat_link')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ca413363b0cc9f76ae0b');
            $table->index('account_id', 'ix_6b302f8e16024e1c1b807206');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_business_chat_link_input_business_ch_2c7a8d471020');
        Schema::dropIfExists('tl_input_business_chat_link_input_business_chat_link');
        Schema::dropIfExists('tl_input_business_chat_link');
    }
};
