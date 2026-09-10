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
        Schema::create('tl_messages_affected_messages_affected_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('pts');
            $table->integer('pts_count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4cbb79b943e17cd87159ccfd');
            $table->index('account_id', 'ix_ba48e70a065fcb6db7e040a6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_affected_messages_affected_messages');
    }
};
