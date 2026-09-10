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
        Schema::create('tl_contacts_found_found', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9ef1dd331aa4326d8a567a44');
            $table->index('account_id', 'ix_9fb5e78cdfdb9415b1057234');
        });
        Schema::create('tl_contacts_found_found__my_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_found_found', 'id', 'fk_9071a0f1cbb853302ff14681')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1c2c9da56c7f570f2be7');
            $table->index('account_id', 'ix_53078a1c5b3185023460d5b1');
        });
        Schema::create('tl_contacts_found_found__results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_found_found', 'id', 'fk_38c86114ad80321da5670a21')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b72f593495bdd4866ae9');
            $table->index('account_id', 'ix_a163d7199f42eb3d2e7d219b');
        });
        Schema::create('tl_contacts_found_found__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_found_found', 'id', 'fk_f939a0f46367ce688c543b6f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ebc1c668fe3334c34743');
            $table->index('account_id', 'ix_62a5d4225a97b69e2f033848');
        });
        Schema::create('tl_contacts_found_found__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_found_found', 'id', 'fk_4164b4891224611e2944b913')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e93204c2d5dfd2291af5');
            $table->index('account_id', 'ix_5901912faffb2fb6668c374e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_found_found__users');
        Schema::dropIfExists('tl_contacts_found_found__chats');
        Schema::dropIfExists('tl_contacts_found_found__results');
        Schema::dropIfExists('tl_contacts_found_found__my_results');
        Schema::dropIfExists('tl_contacts_found_found');
    }
};
