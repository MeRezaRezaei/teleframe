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
        Schema::create('tl_popular_contact_popular_contact', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('client_id')->nullable();
            $table->index('client_id', 'ix_d8d727d71f36a415f64ed421');
            $table->integer('importers')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9f7c7d5b69f9cc3bdfc3a367');
            $table->index('account_id', 'ix_c5570944d6675e876a752b78');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_popular_contact_popular_contact');
    }
};
