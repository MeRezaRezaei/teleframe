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
        Schema::create('tl_account_content_settings_content_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('sensitive_enabled')->default(false);
            $table->boolean('sensitive_can_change')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2328d9cb4a4b4365d582e4c8');
            $table->index('account_id', 'ix_c6eb7b5391bb77c04af64ff1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_content_settings_content_settings');
    }
};
