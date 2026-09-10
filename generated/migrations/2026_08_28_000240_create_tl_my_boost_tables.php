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
        Schema::create('tl_my_boost_my_boost', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('slot')->nullable();
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_d27dd773e9e2541a7ae50327');
            $table->integer('date')->nullable();
            $table->integer('expires')->nullable();
            $table->integer('cooldown_until_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_487410235e93b7cf3072aff2');
            $table->index('account_id', 'ix_80a3e376b6435db3db41b618');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_my_boost_my_boost');
    }
};
