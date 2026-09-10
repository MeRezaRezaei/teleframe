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
        Schema::create('tl_phone_exported_group_call_invite_exported__9f796a593d9b', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('link')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e13f78ea37723d3568bb9741');
            $table->index('account_id', 'ix_e9aac683a1bc71a5e993bbc2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_exported_group_call_invite_exported__9f796a593d9b');
    }
};
