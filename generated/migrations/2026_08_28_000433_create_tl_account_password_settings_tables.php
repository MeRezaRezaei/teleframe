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
        Schema::create('tl_account_password_settings_password_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('email')->nullable();
            $table->bigInteger('secure_settings')->nullable();
            $table->index('secure_settings', 'ix_c4f970ca366331fea399ef3e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_310576c844c0f3df3cc88c98');
            $table->index('account_id', 'ix_51a56f659a75fb3c9fb9ca0e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_password_settings_password_settings');
    }
};
