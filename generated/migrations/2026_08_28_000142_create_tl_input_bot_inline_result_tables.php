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
        Schema::create('tl_input_bot_inline_result_input_bot_inline_result', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id')->nullable();
            $table->text('tl_type')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('thumb')->nullable();
            $table->index('thumb', 'ix_35e4a2a0b4a8b557a9d58a00');
            $table->bigInteger('content')->nullable();
            $table->index('content', 'ix_b0720f9798231bf4b46afa48');
            $table->bigInteger('send_message')->nullable();
            $table->index('send_message', 'ix_9e1db7c2c0614b905229cc19');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_affb0e79df8b65bae737b223');
            $table->index('account_id', 'ix_a01a3d7bbbd30d2a6e24c504');
        });
        Schema::create('tl_input_bot_inline_result_input_bot_inline_r_ddd2d6c152ff', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id')->nullable();
            $table->text('tl_type')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_b42002e4927275f827ff1fce');
            $table->bigInteger('send_message')->nullable();
            $table->index('send_message', 'ix_9e7ef73e075c2a80a83d98ab');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0e3035b5fd6aa5ffd9844f93');
            $table->index('account_id', 'ix_19ed3fc70c4db417c1300f5c');
        });
        Schema::create('tl_input_bot_inline_result_input_bot_inline_result_game', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_id')->nullable();
            $table->text('short_name')->nullable();
            $table->bigInteger('send_message')->nullable();
            $table->index('send_message', 'ix_b8c8dc532f372fa3f77c6ce4');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2e03ac79e8122ae701c50827');
            $table->index('account_id', 'ix_3724d0b654035c2df50613a4');
        });
        Schema::create('tl_input_bot_inline_result_input_bot_inline_result_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_id')->nullable();
            $table->text('tl_type')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_6e0c8a3fca82db0464dd1bc5');
            $table->bigInteger('send_message')->nullable();
            $table->index('send_message', 'ix_78717efcff3a9bc31b84b33d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3bfc97b3a785ea17e700febe');
            $table->index('account_id', 'ix_9dfa91c7fbd83cbe0f0650c1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_bot_inline_result_input_bot_inline_result_photo');
        Schema::dropIfExists('tl_input_bot_inline_result_input_bot_inline_result_game');
        Schema::dropIfExists('tl_input_bot_inline_result_input_bot_inline_r_ddd2d6c152ff');
        Schema::dropIfExists('tl_input_bot_inline_result_input_bot_inline_result');
    }
};
