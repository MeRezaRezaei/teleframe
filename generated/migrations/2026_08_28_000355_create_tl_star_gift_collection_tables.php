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
        Schema::create('tl_star_gift_collection_star_gift_collection', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('collection_id')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('icon')->nullable();
            $table->index('icon', 'ix_ef2752688d46654bb593923f');
            $table->integer('gifts_count')->nullable();
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_310212b8d4247c018c4eddc3');
            $table->index('account_id', 'ix_d826b765fe08645975f89389');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_collection_star_gift_collection');
    }
};
