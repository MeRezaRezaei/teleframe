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
        Schema::create('tl_star_gift_attribute_counter', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_6c63378d9116a51c24a3f521');
            $table->index('account_id', 'ix_703d520ef6d95f6dfbb672d0');
        });
        Schema::create('tl_star_gift_attribute_counter_star_gift_attribute_counter', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute_counter')->cascadeOnDelete();
            $table->uuid('attribute');
            $table->index('attribute', 'ix_bccd137c81794350b9dc8e9a');
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4acb3aed5c11350cd9cd0e61');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_attribute_counter_star_gift_attribute_counter');
        Schema::dropIfExists('tl_star_gift_attribute_counter');
    }
};
