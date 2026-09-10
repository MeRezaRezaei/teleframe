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
        Schema::create('tl_chat_admin_with_invites_chat_admin_with_invites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('admin_id')->nullable();
            $table->index('admin_id', 'ix_85e5775535e175cc98a3b7b5');
            $table->integer('invites_count')->nullable();
            $table->integer('revoked_invites_count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b20bbbc8f2c4b98e45ab402b');
            $table->index('account_id', 'ix_76b17b7046391f4186cb2fa9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_admin_with_invites_chat_admin_with_invites');
    }
};
