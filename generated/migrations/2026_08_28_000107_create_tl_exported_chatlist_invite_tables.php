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
        Schema::create('tl_exported_chatlist_invite_exported_chatlist_invite', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('title')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2f881937b16d622acd817662');
            $table->index('account_id', 'ix_068c0562a8ea5e5367517f6b');
        });
        Schema::create('tl_exported_chatlist_invite_exported_chatlist_fe83e2c1c582', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_0a748316f2ca1ee2fa056e2b')->references('id')->on('tl_exported_chatlist_invite_exported_chatlist_invite')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_17f1fa26b9f658eab0c3');
            $table->index('account_id', 'ix_00513d3fee6cd6c68add23af');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_exported_chatlist_invite_exported_chatlist_fe83e2c1c582');
        Schema::dropIfExists('tl_exported_chatlist_invite_exported_chatlist_invite');
    }
};
