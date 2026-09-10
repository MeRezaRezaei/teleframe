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
        Schema::create('tl_input_app_event_input_app_event', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->double('time')->nullable();
            $table->text('tl_type')->nullable();
            $table->bigInteger('peer')->nullable();
            $table->bigInteger('data')->nullable();
            $table->index('data', 'ix_3d8ce4e9e5adc7d9e5d43eff');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c66f1ce8aca5ac284b3b009c');
            $table->index('account_id', 'ix_5099741446bd0184e494ea72');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_app_event_input_app_event');
    }
};
