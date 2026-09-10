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
        Schema::create('tl_secure_secret_settings_secure_secret_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('secure_algo')->nullable();
            $table->index('secure_algo', 'ix_7f38662a7b328e2644329b65');
            $table->binary('secure_secret')->nullable();
            $table->bigInteger('secure_secret_id')->nullable();
            $table->index('secure_secret_id', 'ix_19b6c87ed5883011792fdbc3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ec75f095469956b6db264c1e');
            $table->index('account_id', 'ix_ad95d089f6b6226b5071cd23');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_secret_settings_secure_secret_settings');
    }
};
