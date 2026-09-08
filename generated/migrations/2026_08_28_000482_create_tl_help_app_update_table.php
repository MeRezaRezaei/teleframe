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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_help_app_update_app_update', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_app_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('can_not_skip')->default(false);
            $table->integer('tl_id');
            $table->text('version');
            $table->text('text');
            $table->uuid('document')->nullable();
            $table->text('url')->nullable();
            $table->uuid('sticker')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_help_app_update_app_update__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_app_update_app_update')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4058f1bc17a486372ee3');
        });
        Schema::create('tl_help_app_update_no_app_update', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_app_update')->cascadeOnDelete();
            $table->timestamps();
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
