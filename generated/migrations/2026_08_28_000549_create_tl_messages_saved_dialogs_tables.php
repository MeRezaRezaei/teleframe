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
        Schema::create('tl_messages_saved_dialogs_saved_dialogs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0a2024b0b7133137dda5d609');
            $table->index('account_id', 'ix_234cb4f1c45115d097adf93d');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs__dialogs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs', 'id', 'fk_ec2d2d4d77a2afe6f62336c9')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8055eec565ef09bc893a');
            $table->index('account_id', 'ix_6a970f759f3ba3c8bb631432');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs__messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs', 'id', 'fk_4954b6f29b01cc1f864d2168')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6a3c4e68ab534e7250b5');
            $table->index('account_id', 'ix_f3e1d0109b9c1b89feec03b5');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs', 'id', 'fk_a7dd771b6c3f80dd26c6eda4')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_81fec5f724cfbe2a390f');
            $table->index('account_id', 'ix_d17556528bc78f8bd20760d5');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs', 'id', 'fk_dcfc3f8d55d4e420fb3e0894')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_42bb141cc9c7c0b416c3');
            $table->index('account_id', 'ix_be4519f7e2992a05fcb1ac42');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e39bed6dad929442e61390a8');
            $table->index('account_id', 'ix_45ebc405bae42d726250a3d4');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6c0c13d398b74db104d55ddc');
            $table->index('account_id', 'ix_6d66215c3f27a166ac90e96f');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice__dialogs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs_slice', 'id', 'fk_34d39d57857e97eaa612976d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_946993988d590f736ccc');
            $table->index('account_id', 'ix_632ec2cf32ba785f0fd9bc59');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice__messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs_slice', 'id', 'fk_9dc25a7e7858535ec4803c78')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_579fc484cc9773180e26');
            $table->index('account_id', 'ix_c91097e0d6ca1705bb2ed281');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs_slice', 'id', 'fk_724a35b85eb495d460cfe089')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0cb78cf8f438489185e9');
            $table->index('account_id', 'ix_0131004d10d4199c072fd4cf');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs_slice', 'id', 'fk_25a6be7decfaa0b14671ce5c')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_492ffbc2922b468184af');
            $table->index('account_id', 'ix_8136abf801fe1856cb81922c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs_slice__users');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs_slice__chats');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs_slice__messages');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs_slice__dialogs');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs_slice');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs_not_modified');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs__users');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs__chats');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs__messages');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs__dialogs');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs');
    }
};
