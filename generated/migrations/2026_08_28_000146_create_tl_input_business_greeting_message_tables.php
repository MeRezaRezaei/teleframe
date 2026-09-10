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
        Schema::create('tl_input_business_greeting_message_input_busi_21ffd04e8009', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('shortcut_id')->nullable();
            $table->bigInteger('recipients')->nullable();
            $table->index('recipients', 'ix_310e5db613ef1becc0d07514');
            $table->integer('no_activity_days')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3e04a4d46a8098a6c5791604');
            $table->index('account_id', 'ix_e869b16bcfa0998b46ba6d43');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_business_greeting_message_input_busi_21ffd04e8009');
    }
};
