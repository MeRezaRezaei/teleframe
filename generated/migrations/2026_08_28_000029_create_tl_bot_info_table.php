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
        Schema::create('tl_bot_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_db28d5254c5bc2188057d88d');
            $table->index('account_id', 'ix_863096436259fdce310b58d0');
        });
        Schema::create('tl_bot_info_bot_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_info')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_preview_medias')->default(false);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_d84c49d92b02332b10ae8418');
            $table->text('description')->nullable();
            $table->uuid('description_photo')->nullable();
            $table->index('description_photo', 'ix_e9b411c1cea84240fc7a5699');
            $table->uuid('description_document')->nullable();
            $table->index('description_document', 'ix_7c668e7fee3879e7f74fc123');
            $table->uuid('menu_button')->nullable();
            $table->index('menu_button', 'ix_fda1bc435261dde82284a103');
            $table->text('privacy_policy_url')->nullable();
            $table->uuid('app_settings')->nullable();
            $table->index('app_settings', 'ix_33e967f29fc007a73bcfe192');
            $table->uuid('verifier_settings')->nullable();
            $table->index('verifier_settings', 'ix_d46b1a5d13bcae5d46a76cf8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fb456c06a1a4f56c4d5dbbb8');
        });
        Schema::create('tl_bot_info_bot_info__commands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bot_info_bot_info')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_83b2913c3a7e547726ab');
            $table->index('account_id', 'ix_6bd8d0eae8bf9316a78d4d5c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_info_bot_info__commands');
        Schema::dropIfExists('tl_bot_info_bot_info');
        Schema::dropIfExists('tl_bot_info');
    }
};
