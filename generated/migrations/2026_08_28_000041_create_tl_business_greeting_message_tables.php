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
        Schema::create('tl_business_greeting_message_business_greeting_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('shortcut_id')->nullable();
            $table->bigInteger('recipients')->nullable();
            $table->index('recipients', 'ix_1ade913ea8b89a133edf0015');
            $table->integer('no_activity_days')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1bc8b5d69ba5a56d1747c34f');
            $table->index('account_id', 'ix_afb5aa13f02968b8359c9cc0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_greeting_message_business_greeting_message');
    }
};
