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
        Schema::create('tl_messages_votes_list_votes_list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('count');
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_301c3963e062397f7a0f91ee');
            $table->index('account_id', 'ix_6b4af7856a4eb284c5a32e4b');
        });
        Schema::create('tl_messages_votes_list_votes_list__votes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_votes_list_votes_list', 'id', 'fk_6a704cba8a43790d12fb8ea8')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4a4444076db8b8124f52');
            $table->index('account_id', 'ix_74b7ce385853bea27d957bbc');
        });
        Schema::create('tl_messages_votes_list_votes_list__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_votes_list_votes_list', 'id', 'fk_43999981575c3db63b6989ac')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1d0a41f052d15c42e42e');
            $table->index('account_id', 'ix_19d22c22cca79ab619466ca6');
        });
        Schema::create('tl_messages_votes_list_votes_list__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_votes_list_votes_list', 'id', 'fk_a12731670abf34013fde8eeb')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4c48215bd28d57d286fc');
            $table->index('account_id', 'ix_658bbd4b70c09a64f75682aa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_votes_list_votes_list__users');
        Schema::dropIfExists('tl_messages_votes_list_votes_list__chats');
        Schema::dropIfExists('tl_messages_votes_list_votes_list__votes');
        Schema::dropIfExists('tl_messages_votes_list_votes_list');
    }
};
