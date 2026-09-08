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
        Schema::create('tl_post_address', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_post_address_post_address', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_post_address')->cascadeOnDelete();
            $table->text('street_line1');
            $table->text('street_line2');
            $table->text('city');
            $table->text('state');
            $table->text('country_iso2');
            $table->text('post_code');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_post_address_post_address');
        Schema::dropIfExists('tl_post_address');
    }
};
