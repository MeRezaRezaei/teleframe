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
        Schema::create('tl_document_attribute_document_attribute_animated', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e601e8a1ede674e21184cd6b');
            $table->index('account_id', 'ix_1c8f0a660b638a2ef62c864f');
        });
        Schema::create('tl_document_attribute_document_attribute_audio', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('voice')->default(false);
            $table->integer('duration')->nullable();
            $table->text('title')->nullable();
            $table->text('performer')->nullable();
            $table->binary('waveform')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f84055be316ba1652250ec0e');
            $table->index('account_id', 'ix_0e8e0b59688602d0603ffc17');
        });
        Schema::create('tl_document_attribute_document_attribute_custom_emoji', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('free')->default(false);
            $table->boolean('text_color')->default(false);
            $table->text('alt')->nullable();
            $table->bigInteger('stickerset')->nullable();
            $table->index('stickerset', 'ix_a9149f0a620255b856f3c3db');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_642d989884b242db3dcc35dd');
            $table->index('account_id', 'ix_ee23e81892b05592c5efc8c0');
        });
        Schema::create('tl_document_attribute_document_attribute_filename', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('file_name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_685a58f954f332d632e6ea9f');
            $table->index('account_id', 'ix_717c03fe9473172e6b73e2db');
        });
        Schema::create('tl_document_attribute_document_attribute_has_stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a5eb9cbc699900ee7893cc14');
            $table->index('account_id', 'ix_42a4b2450a55dbb7028ea16c');
        });
        Schema::create('tl_document_attribute_document_attribute_image_size', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4731194f6e6ae707746d5ffc');
            $table->index('account_id', 'ix_1529ce3cb6c3add182acb59e');
        });
        Schema::create('tl_document_attribute_document_attribute_sticker', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('mask')->default(false);
            $table->text('alt')->nullable();
            $table->bigInteger('stickerset')->nullable();
            $table->index('stickerset', 'ix_f9fb332bf0026f726e8ecd7a');
            $table->bigInteger('mask_coords')->nullable();
            $table->index('mask_coords', 'ix_e2e91f51b3c4c1f8670be618');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8948ed0ea6529bfecf00083e');
            $table->index('account_id', 'ix_23df06661c3913370506d66c');
        });
        Schema::create('tl_document_attribute_document_attribute_video', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('round_message')->default(false);
            $table->boolean('supports_streaming')->default(false);
            $table->boolean('nosound')->default(false);
            $table->double('duration')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->integer('preload_prefix_size')->nullable();
            $table->double('video_start_ts')->nullable();
            $table->text('video_codec')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_cf291621fb79efd756067d64');
            $table->index('account_id', 'ix_1601c1dbe24e7985935231b1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_document_attribute_document_attribute_video');
        Schema::dropIfExists('tl_document_attribute_document_attribute_sticker');
        Schema::dropIfExists('tl_document_attribute_document_attribute_image_size');
        Schema::dropIfExists('tl_document_attribute_document_attribute_has_stickers');
        Schema::dropIfExists('tl_document_attribute_document_attribute_filename');
        Schema::dropIfExists('tl_document_attribute_document_attribute_custom_emoji');
        Schema::dropIfExists('tl_document_attribute_document_attribute_audio');
        Schema::dropIfExists('tl_document_attribute_document_attribute_animated');
    }
};
