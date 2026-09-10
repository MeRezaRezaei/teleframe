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
        Schema::create('tl_web_view_message_sent_web_view_message_sent', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('msg_id')->nullable();
            $table->index('msg_id', 'ix_1d1869e387cc9efec4e93676');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b48eed36413c5034d71be97b');
            $table->index('account_id', 'ix_7c3f6a272859126aad144d6d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_view_message_sent_web_view_message_sent');
    }
};
