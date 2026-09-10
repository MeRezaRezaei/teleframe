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
        Schema::create('tl_chat_invite_importer_chat_invite_importer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('requested')->default(false);
            $table->boolean('via_chatlist')->default(false);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_e483d33d10df7eecdecaa1c2');
            $table->integer('date')->nullable();
            $table->text('about')->nullable();
            $table->bigInteger('approved_by')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_48e7de97ae9a10769e6f7a03');
            $table->index('account_id', 'ix_901a4bc4b96faa3e86689201');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_invite_importer_chat_invite_importer');
    }
};
