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
        Schema::create('tl_contact_contact', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_e97cd830b985eb33ea5859af');
            $table->bigInteger('mutual')->nullable();
            $table->index('mutual', 'ix_0e8a331c353a58bdb27bbc38');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a0c64cdd1b98cc812076ef6f');
            $table->index('account_id', 'ix_c4f5663e1ce1af19341777d8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contact_contact');
    }
};
