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
        Schema::create('tl_my_boost', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_11340c4cd789c07fb1d51c74');
            $table->index('account_id', 'ix_f9334e25f592875f59eca94a');
        });
        Schema::create('tl_my_boost_my_boost', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_my_boost')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('slot');
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_d27dd773e9e2541a7ae50327');
            $table->integer('date');
            $table->integer('expires');
            $table->integer('cooldown_until_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_80a3e376b6435db3db41b618');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_my_boost_my_boost');
        Schema::dropIfExists('tl_my_boost');
    }
};
