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
        Schema::create('tl_r_s_a_public_key_rsa_public_key', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('n')->nullable();
            $table->text('e')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0e833e8296f289b17097cb5d');
            $table->index('account_id', 'ix_ff1621c71f72d1643b2b8077');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_r_s_a_public_key_rsa_public_key');
    }
};
