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
        Schema::create('tl_input_channel_input_channel', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('channel_id')->nullable();
            $table->index('channel_id', 'ix_1efbdcb9d6f07a65d823aa64');
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4aaafae700a2e0cc281c7bce');
            $table->index('account_id', 'ix_74523b6a75e533b35539a701');
        });
        Schema::create('tl_input_channel_input_channel_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_adb90a8222035573887c28a4');
            $table->index('account_id', 'ix_0887ef4e32ccadd5f489647c');
        });
        Schema::create('tl_input_channel_input_channel_from_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_8069f24647d6405587d4f6d7');
            $table->integer('msg_id')->nullable();
            $table->bigInteger('channel_id')->nullable();
            $table->index('channel_id', 'ix_5423c5dd462f8d2affa21726');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_849b6fc49bd09ac0fa706f65');
            $table->index('account_id', 'ix_1b2be8c901359e4742de79b6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_channel_input_channel_from_message');
        Schema::dropIfExists('tl_input_channel_input_channel_empty');
        Schema::dropIfExists('tl_input_channel_input_channel');
    }
};
