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
        Schema::create('tl_public_forward_public_forward_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('message')->nullable();
            $table->index('message', 'ix_3996ebc337728d309e7c33aa');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4e7a8943a78b9ed30a38fe4d');
            $table->index('account_id', 'ix_693fcd3af8cdbc7bc6d11adb');
        });
        Schema::create('tl_public_forward_public_forward_story', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_fa3cf92ba9eb94ea9e4b88d8');
            $table->bigInteger('story')->nullable();
            $table->index('story', 'ix_cdcba058650ae64875ba8caa');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c23f8a49b4be52ece78cd406');
            $table->index('account_id', 'ix_df52205d5a60d0873ffec9a9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_public_forward_public_forward_story');
        Schema::dropIfExists('tl_public_forward_public_forward_message');
    }
};
