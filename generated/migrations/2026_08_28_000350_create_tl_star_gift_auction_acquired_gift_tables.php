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
        Schema::create('tl_star_gift_auction_acquired_gift_star_gift__f6508cc9bcc2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('name_hidden')->default(false);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_38d835534c522503c17f6b06');
            $table->integer('date')->nullable();
            $table->bigInteger('bid_amount')->nullable();
            $table->integer('round')->nullable();
            $table->integer('pos')->nullable();
            $table->bigInteger('message')->nullable();
            $table->index('message', 'ix_27650ca6719e41c77e419e4a');
            $table->integer('gift_num')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_43b136f08672191916d9d75c');
            $table->index('account_id', 'ix_e87f954c06facdcc5e898a9c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_auction_acquired_gift_star_gift__f6508cc9bcc2');
    }
};
