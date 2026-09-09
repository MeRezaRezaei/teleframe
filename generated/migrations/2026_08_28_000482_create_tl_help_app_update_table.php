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
        Schema::create('tl_help_app_update', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c64703f1dc826d36cd349d7e');
            $table->index('account_id', 'ix_9aca083c163531988be20d81');
        });
        Schema::create('tl_help_app_update_app_update', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_app_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('can_not_skip')->default(false);
            $table->integer('tl_id');
            $table->text('version');
            $table->text('text');
            $table->uuid('document')->nullable();
            $table->index('document', 'ix_0b93c5fe1daf21c6d088ec59');
            $table->text('url')->nullable();
            $table->uuid('sticker')->nullable();
            $table->index('sticker', 'ix_2a6cf58c8f975d10b4b354c6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1a276183f9373b54765bb684');
            $table->unique(['account_id', 'tl_id'], 'ux_b8e321f82dcd20b62334');
        });
        Schema::create('tl_help_app_update_app_update__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_app_update_app_update')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4058f1bc17a486372ee3');
            $table->index('account_id', 'ix_9d01c817eeaec5a519e79867');
        });
        Schema::create('tl_help_app_update_no_app_update', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_app_update')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3e96b7330501914c77cfa479');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_app_update_no_app_update');
        Schema::dropIfExists('tl_help_app_update_app_update__entities');
        Schema::dropIfExists('tl_help_app_update_app_update');
        Schema::dropIfExists('tl_help_app_update');
    }
};
