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
        Schema::create('tl_group_call_donor_group_call_donor', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('top')->default(false);
            $table->boolean('my')->default(false);
            $table->bigInteger('peer_id')->nullable();
            $table->index('peer_id', 'ix_6ab572dc9acdeb982de95629');
            $table->bigInteger('stars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a01e4b3163fb1743e8ece85b');
            $table->index('account_id', 'ix_946bedb8976d7bf41a6da42f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_donor_group_call_donor');
    }
};
