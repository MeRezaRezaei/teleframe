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
        Schema::create('tl_messages_sponsored_messages_sponsored_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('posts_between')->nullable();
            $table->integer('start_delay')->nullable();
            $table->integer('between_delay')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c496fe7fe575794518567f16');
            $table->index('account_id', 'ix_92767ce76314f5213f2c5519');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_mess_8fd982913adc', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_1b753847ae5c63208d5aa8e1')->references('id')->on('tl_messages_sponsored_messages_sponsored_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1ec8e14f08f1b3ccd227');
            $table->index('account_id', 'ix_ff62278c455383dada3063a6');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_messages__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_0ab449c7c7eb2166af61e32b')->references('id')->on('tl_messages_sponsored_messages_sponsored_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_825ed9e4db8b88fbebfe');
            $table->index('account_id', 'ix_ea84a89a79160c556eaf9a7d');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_messages__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_1be0cbe4712044b48a2a741f')->references('id')->on('tl_messages_sponsored_messages_sponsored_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5c6642e9d55463a138e8');
            $table->index('account_id', 'ix_50cf0793d0101641ef75dbf7');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_messages_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c4169afc9f2643107f487bdd');
            $table->index('account_id', 'ix_138753c635a543699afa6dcb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_sponsored_messages_sponsored_messages_empty');
        Schema::dropIfExists('tl_messages_sponsored_messages_sponsored_messages__users');
        Schema::dropIfExists('tl_messages_sponsored_messages_sponsored_messages__chats');
        Schema::dropIfExists('tl_messages_sponsored_messages_sponsored_mess_8fd982913adc');
        Schema::dropIfExists('tl_messages_sponsored_messages_sponsored_messages');
    }
};
