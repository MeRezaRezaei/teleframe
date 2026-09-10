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
        Schema::create('tl_secure_plain_data_secure_plain_email', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('email')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_473c80bad6710f5fb8643202');
            $table->index('account_id', 'ix_5f1309f9e860f32661730133');
        });
        Schema::create('tl_secure_plain_data_secure_plain_phone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('phone')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8d6a2a5497f5307e9fcc33de');
            $table->index('account_id', 'ix_620b7b6899172302ebde4cc0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_plain_data_secure_plain_phone');
        Schema::dropIfExists('tl_secure_plain_data_secure_plain_email');
    }
};
