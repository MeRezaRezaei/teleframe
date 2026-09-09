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
            $table->index('constructor_id', 'ix_ee6699a1f1ea51d7e4ef26c2');
            $table->index('account_id', 'ix_de3972c6d6c4ffecf122de64');
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
            $table->bigInteger('from_id')->nullable();
            $table->index('from_id', 'ix_052dafe4f959fdd50c8b6cfb');
            $table->integer('date');
            $table->uuid('gift');
            $table->index('gift', 'ix_48f780f4af98b0bc14518d9e');
            $table->uuid('message')->nullable();
            $table->index('message', 'ix_de51aa6b0cfb9618070a39a3');
            $table->integer('msg_id')->nullable();
            $table->bigInteger('saved_id')->nullable();
            $table->index('saved_id', 'ix_3372987d5a1836b73688f923');
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
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f9c0ae0d61bc971be64bf8b5');
        });
        Schema::create('tl_saved_star_gift_saved_star_gift__collection_id', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_saved_star_gift_saved_star_gift')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4059e4648e6a8e8b7393');
            $table->index('account_id', 'ix_213d67d933f1c9d50a973669');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_saved_star_gift_saved_star_gift__collection_id');
        Schema::dropIfExists('tl_saved_star_gift_saved_star_gift');
        Schema::dropIfExists('tl_saved_star_gift');
    }
};
