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
        Schema::create('tl_missing_invitee_missing_invitee', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('premium_would_allow_invite')->default(false);
            $table->boolean('premium_required_for_pm')->default(false);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_f587481cd0c413946e9d702f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d4299a520b7697416038ae86');
            $table->index('account_id', 'ix_bf69b6cd7472070d6ac3bf24');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_missing_invitee_missing_invitee');
    }
};
