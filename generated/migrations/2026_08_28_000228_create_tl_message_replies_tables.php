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
        Schema::create('tl_message_replies_message_replies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('comments')->default(false);
            $table->integer('replies')->nullable();
            $table->integer('replies_pts')->nullable();
            $table->bigInteger('channel_id')->nullable();
            $table->index('channel_id', 'ix_92ced2e6d1d5d6432734c46b');
            $table->integer('max_id')->nullable();
            $table->integer('read_max_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_20c2b8c934278eb60165d2f6');
            $table->index('account_id', 'ix_94941f184101072113724d89');
        });
        Schema::create('tl_message_replies_message_replies__recent_repliers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_message_replies_message_replies', 'id', 'fk_b04fd4d527ec150a5e519175')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a8d39e46c83f8dc08989');
            $table->index('account_id', 'ix_580bb60faad624f59d493f6d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_replies_message_replies__recent_repliers');
        Schema::dropIfExists('tl_message_replies_message_replies');
    }
};
