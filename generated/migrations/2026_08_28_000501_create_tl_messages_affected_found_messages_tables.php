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
        Schema::create('tl_messages_affected_found_messages_affected__d0b5b58c5216', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('pts')->nullable();
            $table->integer('pts_count')->nullable();
            $table->integer('tl_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d0ad0d9e936aea329c13f958');
            $table->index('account_id', 'ix_b1df268f557c11357cdea7e6');
        });
        Schema::create('tl_messages_affected_found_messages_affected__84127c85d979', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_affected_found_messages_affected__d0b5b58c5216', 'id', 'fk_928fb01080a90c1a37032aad')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7913711edc1702816a8d');
            $table->index('account_id', 'ix_1d9cd4a6a3fa83a88ad5cc22');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_affected_found_messages_affected__84127c85d979');
        Schema::dropIfExists('tl_messages_affected_found_messages_affected__d0b5b58c5216');
    }
};
