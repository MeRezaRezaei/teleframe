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
        Schema::create('tl_star_gift_collection', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_889be088fccf5153ab4becc0');
            $table->index('account_id', 'ix_5fc28c2f0b0aed4945d35a4c');
        });
        Schema::create('tl_star_gift_collection_star_gift_collection', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_collection')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('collection_id');
            $table->text('title');
            $table->uuid('icon')->nullable();
            $table->index('icon', 'ix_ef2752688d46654bb593923f');
            $table->integer('gifts_count');
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d826b765fe08645975f89389');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_collection_star_gift_collection');
        Schema::dropIfExists('tl_star_gift_collection');
    }
};
