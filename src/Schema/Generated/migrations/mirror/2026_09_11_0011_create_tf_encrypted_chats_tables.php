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
        Schema::create('tf_encrypted_chats', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->integer('id')->unsigned();
        $table->text('constructor');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('date')->unsigned();
        $table->bigInteger('admin_id')->unsigned();
        $table->bigInteger('participant_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_encrypted_chats');

        Schema::dropIfExists('tf_encrypted_chats');

    }
};
