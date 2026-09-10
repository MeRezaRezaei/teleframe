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
        Schema::create('tl_star_gift_attribute_counter_star_gift_attribute_counter', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('attribute')->nullable();
            $table->index('attribute', 'ix_bccd137c81794350b9dc8e9a');
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_55616fbf839b68c3c098c0cf');
            $table->index('account_id', 'ix_4acb3aed5c11350cd9cd0e61');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_attribute_counter_star_gift_attribute_counter');
    }
};
