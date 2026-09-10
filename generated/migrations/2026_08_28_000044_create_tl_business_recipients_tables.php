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
        Schema::create('tl_business_recipients_business_recipients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('existing_chats')->default(false);
            $table->boolean('new_chats')->default(false);
            $table->boolean('contacts')->default(false);
            $table->boolean('non_contacts')->default(false);
            $table->boolean('exclude_selected')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7ee62da2c4232b5e01bf2180');
            $table->index('account_id', 'ix_6db6d08e4a01b1bc51f014f5');
        });
        Schema::create('tl_business_recipients_business_recipients__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_75ef9e6fe14834a4a7c74f94')->references('id')->on('tl_business_recipients_business_recipients')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_35a0258263fd39436697');
            $table->index('account_id', 'ix_3a5684e505f7826282ea82d0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_recipients_business_recipients__users');
        Schema::dropIfExists('tl_business_recipients_business_recipients');
    }
};
