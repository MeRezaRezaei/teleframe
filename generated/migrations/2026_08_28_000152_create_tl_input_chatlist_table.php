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
        Schema::create('tl_input_chatlist', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5e1b5c4672296b1ebbde60e1');
            $table->index('account_id', 'ix_5fa7ef1ce5fd1fb38ca18e5f');
        });
        Schema::create('tl_input_chatlist_input_chatlist_dialog_filter', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_chatlist')->cascadeOnDelete();
            $table->integer('filter_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0e58ce4798a8b085e4a5b7a1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_chatlist_input_chatlist_dialog_filter');
        Schema::dropIfExists('tl_input_chatlist');
    }
};
