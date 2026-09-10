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
        Schema::create('tl_message_views_message_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('views')->nullable();
            $table->integer('forwards')->nullable();
            $table->bigInteger('replies')->nullable();
            $table->index('replies', 'ix_ec030d8e1e34c963fadcdd85');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0d53f1b4c236f59b5ce152c0');
            $table->index('account_id', 'ix_1f069e659d3a23a83fdeff11');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_views_message_views');
    }
};
