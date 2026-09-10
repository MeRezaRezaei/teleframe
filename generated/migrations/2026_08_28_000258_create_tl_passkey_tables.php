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
        Schema::create('tl_passkey_passkey', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id')->nullable();
            $table->text('name')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('software_emoji_id')->nullable();
            $table->index('software_emoji_id', 'ix_9c5b1adacf769e19957c3fdc');
            $table->integer('last_usage_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d45329eba9d7e630da635da9');
            $table->index('account_id', 'ix_68f780dfeb02b7dd44422932');
            $table->unique(['account_id'], 'ux_d0887fa4b9cb9e723c10');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_passkey_passkey');
    }
};
