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
        Schema::create('tl_input_group_call_input_group_call', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8920f04e2cbcce56ef1a4362');
            $table->index('account_id', 'ix_8d3755c2a2519b294ddc9b74');
        });
        Schema::create('tl_input_group_call_input_group_call_invite_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('msg_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_07e14345ab8b1299785b4e56');
            $table->index('account_id', 'ix_6e3d26e4d1bbdf7988a058ba');
        });
        Schema::create('tl_input_group_call_input_group_call_slug', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('slug')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9d99388b08ecf52a85ac54c2');
            $table->index('account_id', 'ix_c5c5959568796af1be2fb9dc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_group_call_input_group_call_slug');
        Schema::dropIfExists('tl_input_group_call_input_group_call_invite_message');
        Schema::dropIfExists('tl_input_group_call_input_group_call');
    }
};
