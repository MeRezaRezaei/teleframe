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
        Schema::create('tl_input_business_intro', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_f6355276d28227826ac275ed');
            $table->index('account_id', 'ix_9593c8389a2d2171168cbdfa');
        });
        Schema::create('tl_input_business_intro_input_business_intro', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_business_intro')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('title');
            $table->text('description');
            $table->uuid('sticker')->nullable();
            $table->index('sticker', 'ix_db109a1b631c73a26abb59f7');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bf6f815ec9c286c35dfbb936');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_business_intro_input_business_intro');
        Schema::dropIfExists('tl_input_business_intro');
    }
};
