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
        Schema::create('tl_updates_state_state', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('pts')->nullable();
            $table->integer('qts')->nullable();
            $table->integer('date')->nullable();
            $table->integer('seq')->nullable();
            $table->integer('unread_count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1519b95e7213a4e9d4b8dd05');
            $table->index('account_id', 'ix_b43040ccfae8172ef4e7f000');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_updates_state_state');
    }
};
