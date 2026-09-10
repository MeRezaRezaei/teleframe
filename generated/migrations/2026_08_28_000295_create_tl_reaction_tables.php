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
        Schema::create('tl_reaction_reaction_custom_emoji', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('document_id')->nullable();
            $table->index('document_id', 'ix_6aa177c1deeeb83f0eba47cf');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_24af31c6a2919cd7a6377e43');
            $table->index('account_id', 'ix_8530d9c7f79959a5ad6357ed');
        });
        Schema::create('tl_reaction_reaction_emoji', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('emoticon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4a354e6940e4eaecdedb3168');
            $table->index('account_id', 'ix_27aedfed124e3bf5900b65e6');
        });
        Schema::create('tl_reaction_reaction_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3ec32d7dde3f004bdd6a00ad');
            $table->index('account_id', 'ix_bd822ab97374b4102bcc02ef');
        });
        Schema::create('tl_reaction_reaction_paid', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0fde7c1ff144afcfcafe723f');
            $table->index('account_id', 'ix_578dc98c5006c6ff69a03020');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_reaction_reaction_paid');
        Schema::dropIfExists('tl_reaction_reaction_empty');
        Schema::dropIfExists('tl_reaction_reaction_emoji');
        Schema::dropIfExists('tl_reaction_reaction_custom_emoji');
    }
};
