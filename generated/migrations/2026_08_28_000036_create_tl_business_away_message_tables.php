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
        Schema::create('tl_business_away_message_business_away_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('offline_only')->default(false);
            $table->integer('shortcut_id')->nullable();
            $table->bigInteger('schedule')->nullable();
            $table->index('schedule', 'ix_0f22de4f709eaa35292bbf10');
            $table->bigInteger('recipients')->nullable();
            $table->index('recipients', 'ix_b75032b91bbea5cf0c3baa3c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_302de8095b71f93a62fcc507');
            $table->index('account_id', 'ix_91fca124ad63cd2dd149212e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_away_message_business_away_message');
    }
};
