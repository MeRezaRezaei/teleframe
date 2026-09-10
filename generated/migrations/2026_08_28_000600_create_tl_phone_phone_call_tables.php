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
        Schema::create('tl_phone_phone_call_phone_call', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('phone_call')->nullable();
            $table->index('phone_call', 'ix_499fceee457dbd2bf73be9c0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6b07f0f650acccd29a7c701a');
            $table->index('account_id', 'ix_222e52baaa8c8fe017a77d19');
        });
        Schema::create('tl_phone_phone_call_phone_call__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_ad5f85c0ba476b4dd6b646cc')->references('id')->on('tl_phone_phone_call_phone_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_59b1688daddafaa9838e');
            $table->index('account_id', 'ix_88e1312a643321e62ff22667');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_phone_call_phone_call__users');
        Schema::dropIfExists('tl_phone_phone_call_phone_call');
    }
};
