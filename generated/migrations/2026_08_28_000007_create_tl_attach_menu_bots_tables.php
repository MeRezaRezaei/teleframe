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
        Schema::create('tl_attach_menu_bots_attach_menu_bots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_513be84038989c7a46cf7b8b');
            $table->index('account_id', 'ix_09bea28b39370f2fe27d0adc');
        });
        Schema::create('tl_attach_menu_bots_attach_menu_bots__bots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_attach_menu_bots_attach_menu_bots', 'id', 'fk_16ea98b7e4a2ac37f339e800')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5cabdba36f49d1ca334e');
            $table->index('account_id', 'ix_8489ac71c7241ea6e7631546');
        });
        Schema::create('tl_attach_menu_bots_attach_menu_bots__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_attach_menu_bots_attach_menu_bots', 'id', 'fk_458369202726d84b77f705c8')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_374b94169b28e854fdee');
            $table->index('account_id', 'ix_f2cf6d350f2906b892d04b12');
        });
        Schema::create('tl_attach_menu_bots_attach_menu_bots_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_05dc33bf03f59745db4adba1');
            $table->index('account_id', 'ix_06ecbfa703c5bd4c42d0cce2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_attach_menu_bots_attach_menu_bots_not_modified');
        Schema::dropIfExists('tl_attach_menu_bots_attach_menu_bots__users');
        Schema::dropIfExists('tl_attach_menu_bots_attach_menu_bots__bots');
        Schema::dropIfExists('tl_attach_menu_bots_attach_menu_bots');
    }
};
