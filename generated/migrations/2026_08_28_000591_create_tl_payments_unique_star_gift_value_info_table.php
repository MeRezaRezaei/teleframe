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
        Schema::create('tl_payments_unique_star_gift_value_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_unique_star_gift_value_info_uniqu_435563956ba2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_unique_star_gift_value_info')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('last_sale_on_fragment')->default(false);
            $table->boolean('value_is_average')->default(false);
            $table->text('currency');
            $table->bigInteger('tl_value');
            $table->integer('initial_sale_date');
            $table->bigInteger('initial_sale_stars');
            $table->bigInteger('initial_sale_price');
            $table->integer('last_sale_date')->nullable();
            $table->bigInteger('last_sale_price')->nullable();
            $table->bigInteger('floor_price')->nullable();
            $table->bigInteger('average_price')->nullable();
            $table->integer('listed_count')->nullable();
            $table->integer('fragment_listed_count')->nullable();
            $table->text('fragment_listed_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_unique_star_gift_value_info_uniqu_435563956ba2');
        Schema::dropIfExists('tl_payments_unique_star_gift_value_info');
    }
};
