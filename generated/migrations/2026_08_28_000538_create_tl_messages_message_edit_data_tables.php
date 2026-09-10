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
        Schema::create('tl_messages_message_edit_data_message_edit_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('caption')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6ab6bbd1972730e1bd998e30');
            $table->index('account_id', 'ix_828f8f5b6017c4539d65e3be');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_message_edit_data_message_edit_data');
    }
};
