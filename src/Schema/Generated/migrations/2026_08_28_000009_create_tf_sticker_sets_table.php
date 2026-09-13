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
        Schema::create('tf_sticker_sets', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->bigInteger('constructor_id');
            $table->bigInteger('account_id');
            $table->bigInteger('access_hash')->nullable();
            $table->text('title')->nullable();
            $table->text('short_name')->nullable();
            $table->integer('count')->nullable();
            $table->jsonb('hashes')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();
            $table->primary(['id', 'account_id']);
            $table->index('account_id', 'ix_tf_sticker_sets_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_sticker_sets');
    }
};
