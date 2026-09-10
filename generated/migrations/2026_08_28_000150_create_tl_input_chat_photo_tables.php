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
        Schema::create('tl_input_chat_photo_input_chat_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->index('tl_id', 'ix_048446aef3b1a988c7e61d25');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4003250326b1a4e6c891ce56');
            $table->index('account_id', 'ix_d3df28b873f17b4acf607122');
        });
        Schema::create('tl_input_chat_photo_input_chat_photo_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_85b0b85dcf80217ef433e997');
            $table->index('account_id', 'ix_fdd360b89095d0dbd8e2a6c7');
        });
        Schema::create('tl_input_chat_photo_input_chat_uploaded_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('file')->nullable();
            $table->index('file', 'ix_4ddb4405b05c77eda141525b');
            $table->bigInteger('video')->nullable();
            $table->index('video', 'ix_d77d599c993fc2ef2cbb9d81');
            $table->double('video_start_ts')->nullable();
            $table->bigInteger('video_emoji_markup')->nullable();
            $table->index('video_emoji_markup', 'ix_860adae2603659574f616ef4');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_86d843ea47ef4b1de4218fe7');
            $table->index('account_id', 'ix_5473d48a4f1565213fb7427d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_chat_photo_input_chat_uploaded_photo');
        Schema::dropIfExists('tl_input_chat_photo_input_chat_photo_empty');
        Schema::dropIfExists('tl_input_chat_photo_input_chat_photo');
    }
};
