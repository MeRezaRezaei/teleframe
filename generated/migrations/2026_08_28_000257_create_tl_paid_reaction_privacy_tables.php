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
        Schema::create('tl_paid_reaction_privacy_paid_reaction_privacy_anonymous', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f54e226af2c43330eaf3a334');
            $table->index('account_id', 'ix_94d2029fb27f7ce1c21a4933');
        });
        Schema::create('tl_paid_reaction_privacy_paid_reaction_privacy_default', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b56844514b28f3d2a0e4ce0e');
            $table->index('account_id', 'ix_1e90091e6fad85b0e6cfbb57');
        });
        Schema::create('tl_paid_reaction_privacy_paid_reaction_privacy_peer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_533fc21cb928d2cc1ec030cf');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_db3776b75a1ef519c3a3275c');
            $table->index('account_id', 'ix_8fa92fc6fea1770cf0716e70');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_paid_reaction_privacy_paid_reaction_privacy_peer');
        Schema::dropIfExists('tl_paid_reaction_privacy_paid_reaction_privacy_default');
        Schema::dropIfExists('tl_paid_reaction_privacy_paid_reaction_privacy_anonymous');
    }
};
