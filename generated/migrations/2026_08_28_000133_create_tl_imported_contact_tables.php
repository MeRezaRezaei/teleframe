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
        Schema::create('tl_imported_contact_imported_contact', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_df17a703961febe8a4c0e2e1');
            $table->bigInteger('client_id')->nullable();
            $table->index('client_id', 'ix_dcbd5fbe09d46e443595ce72');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4ec7b5a34981fe8763e84333');
            $table->index('account_id', 'ix_00318a75023d61223a2be422');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_imported_contact_imported_contact');
    }
};
