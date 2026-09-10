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
        Schema::create('tl_payments_unique_star_gift_value_info_uniqu_435563956ba2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('last_sale_on_fragment')->default(false);
            $table->boolean('value_is_average')->default(false);
            $table->text('currency')->nullable();
            $table->bigInteger('tl_value')->nullable();
            $table->integer('initial_sale_date')->nullable();
            $table->bigInteger('initial_sale_stars')->nullable();
            $table->bigInteger('initial_sale_price')->nullable();
            $table->integer('last_sale_date')->nullable();
            $table->bigInteger('last_sale_price')->nullable();
            $table->bigInteger('floor_price')->nullable();
            $table->bigInteger('average_price')->nullable();
            $table->integer('listed_count')->nullable();
            $table->integer('fragment_listed_count')->nullable();
            $table->text('fragment_listed_url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c960184892e6162a9633083f');
            $table->index('account_id', 'ix_5bbbd0e4681a083cb6ebaad5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_unique_star_gift_value_info_uniqu_435563956ba2');
    }
};
