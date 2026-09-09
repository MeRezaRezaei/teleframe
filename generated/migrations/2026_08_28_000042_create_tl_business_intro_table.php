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
        Schema::create('tl_business_intro', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_365a6545201fbb8244d4c9ac');
            $table->index('account_id', 'ix_61504d30694b795305090929');
        });
        Schema::create('tl_business_intro_business_intro', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_intro')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('title');
            $table->text('description');
            $table->uuid('sticker')->nullable();
            $table->index('sticker', 'ix_bfaacdfb18092c94624348a5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cec88f966c079f57bd09a022');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_intro_business_intro');
        Schema::dropIfExists('tl_business_intro');
    }
};
