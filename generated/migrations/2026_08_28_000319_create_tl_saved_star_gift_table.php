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
        Schema::create('tl_saved_star_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_saved_star_gift_saved_star_gift', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_saved_star_gift')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('name_hidden')->default(false);
            $table->boolean('unsaved')->default(false);
            $table->boolean('refunded')->default(false);
            $table->boolean('can_upgrade')->default(false);
            $table->boolean('pinned_to_top')->default(false);
            $table->boolean('upgrade_separate')->default(false);
            $table->uuid('from_id')->nullable();
            $table->integer('date');
            $table->uuid('gift');
            $table->uuid('message')->nullable();
            $table->integer('msg_id')->nullable();
            $table->bigInteger('saved_id')->nullable();
            $table->bigInteger('convert_stars')->nullable();
            $table->bigInteger('upgrade_stars')->nullable();
            $table->integer('can_export_at')->nullable();
            $table->bigInteger('transfer_stars')->nullable();
            $table->integer('can_transfer_at')->nullable();
            $table->integer('can_resell_at')->nullable();
            $table->text('prepaid_upgrade_hash')->nullable();
            $table->bigInteger('drop_original_details_stars')->nullable();
            $table->integer('gift_num')->nullable();
            $table->integer('can_craft_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_saved_star_gift_saved_star_gift__collection_id', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_saved_star_gift_saved_star_gift')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4059e4648e6a8e8b7393');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_saved_star_gift_saved_star_gift__collection_id');
        Schema::dropIfExists('tl_saved_star_gift_saved_star_gift');
        Schema::dropIfExists('tl_saved_star_gift');
    }
};
