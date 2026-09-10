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
        Schema::create('tl_post_address_post_address', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('street_line1')->nullable();
            $table->text('street_line2')->nullable();
            $table->text('city')->nullable();
            $table->text('state')->nullable();
            $table->text('country_iso2')->nullable();
            $table->text('post_code')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_87930cb5b70f58b7a14f3551');
            $table->index('account_id', 'ix_123632f1d768e80d6adfc003');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_post_address_post_address');
    }
};
