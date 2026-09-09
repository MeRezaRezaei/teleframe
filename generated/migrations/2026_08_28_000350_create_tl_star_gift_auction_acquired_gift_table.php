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
        Schema::create('tl_star_gift_auction_acquired_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c3f8de63847e41d6462eb1e4');
            $table->index('account_id', 'ix_494d6a230c0665753a1c3d0d');
        });
        Schema::create('tl_star_gift_auction_acquired_gift_star_gift__f6508cc9bcc2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_auction_acquired_gift')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('name_hidden')->default(false);
            $table->bigInteger('peer');
            $table->index('peer', 'ix_38d835534c522503c17f6b06');
            $table->integer('date');
            $table->bigInteger('bid_amount');
            $table->integer('round');
            $table->integer('pos');
            $table->uuid('message')->nullable();
            $table->index('message', 'ix_27650ca6719e41c77e419e4a');
            $table->integer('gift_num')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e87f954c06facdcc5e898a9c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_auction_acquired_gift_star_gift__f6508cc9bcc2');
        Schema::dropIfExists('tl_star_gift_auction_acquired_gift');
    }
};
