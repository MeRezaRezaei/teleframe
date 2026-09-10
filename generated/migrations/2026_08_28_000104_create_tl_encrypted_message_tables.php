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
        Schema::create('tl_encrypted_message_encrypted_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('random_id')->nullable();
            $table->index('random_id', 'ix_fc0c572f6ab8786bce4e7f93');
            $table->integer('chat_id')->nullable();
            $table->integer('date')->nullable();
            $table->binary('bytes')->nullable();
            $table->bigInteger('file')->nullable();
            $table->index('file', 'ix_be2bc37d94cfb1696df01088');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_20ca809fbc0811f688e68718');
            $table->index('account_id', 'ix_00956ed06f08df096ee8de67');
        });
        Schema::create('tl_encrypted_message_encrypted_message_service', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('random_id')->nullable();
            $table->index('random_id', 'ix_991b08a27ecfa14827e67b8c');
            $table->integer('chat_id')->nullable();
            $table->integer('date')->nullable();
            $table->binary('bytes')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_66f8330ef86b1785d0ee69d8');
            $table->index('account_id', 'ix_7727a2bf6f3c8f6799f6b897');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_encrypted_message_encrypted_message_service');
        Schema::dropIfExists('tl_encrypted_message_encrypted_message');
    }
};
