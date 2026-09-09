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
        Schema::create('tl_labeled_price', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_149b8bcdb955818ac47ec12a');
            $table->index('account_id', 'ix_f8ca1505033fd935caf5300a');
        });
        Schema::create('tl_labeled_price_labeled_price', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_labeled_price')->cascadeOnDelete();
            $table->text('label');
            $table->bigInteger('amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ad83567b68aeda1138927b22');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_labeled_price_labeled_price');
        Schema::dropIfExists('tl_labeled_price');
    }
};
