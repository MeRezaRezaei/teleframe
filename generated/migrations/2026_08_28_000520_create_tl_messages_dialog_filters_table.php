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
        Schema::create('tl_messages_dialog_filters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_7c3c8e16d0b75492b96fc417');
            $table->index('account_id', 'ix_40225177ed96bbb602526416');
        });
        Schema::create('tl_messages_dialog_filters_dialog_filters', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_dialog_filters')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('tags_enabled')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fd74f51630ef4d2290af2c98');
        });
        Schema::create('tl_messages_dialog_filters_dialog_filters__filters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialog_filters_dialog_filters')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_258d60b188c602811688');
            $table->index('account_id', 'ix_7e4ef9d1c93ebb40485a3ae7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_dialog_filters_dialog_filters__filters');
        Schema::dropIfExists('tl_messages_dialog_filters_dialog_filters');
        Schema::dropIfExists('tl_messages_dialog_filters');
    }
};
