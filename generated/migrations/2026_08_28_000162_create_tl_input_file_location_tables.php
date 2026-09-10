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
        Schema::create('tl_input_file_location_input_document_file_location', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->binary('file_reference')->nullable();
            $table->text('thumb_size')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ed75f3d1fd4b9dcb448c77fb');
            $table->index('account_id', 'ix_d1aeb88bfb5f92ffe1a72eb5');
        });
        Schema::create('tl_input_file_location_input_encrypted_file_location', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_de397224fdd0537a7afc0e19');
            $table->index('account_id', 'ix_8e5eb12f8b063a7de3f43e0c');
        });
        Schema::create('tl_input_file_location_input_file_location', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('volume_id')->nullable();
            $table->index('volume_id', 'ix_818aa28abce94f2254092ed5');
            $table->integer('local_id')->nullable();
            $table->bigInteger('secret')->nullable();
            $table->binary('file_reference')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c019b6983b6378043d81f648');
            $table->index('account_id', 'ix_e178f8c37f8064301995a1a9');
        });
        Schema::create('tl_input_file_location_input_group_call_stream', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('call')->nullable();
            $table->index('call', 'ix_3c6bacfc2e06e3849d143b5c');
            $table->bigInteger('time_ms')->nullable();
            $table->integer('scale')->nullable();
            $table->integer('video_channel')->nullable();
            $table->integer('video_quality')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_26d648e640a43b54a25edb42');
            $table->index('account_id', 'ix_f6f0f5c30a9e795e7da1bed7');
        });
        Schema::create('tl_input_file_location_input_peer_photo_file_location', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('big')->default(false);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_07bc8925dcce54c0967072a9');
            $table->bigInteger('photo_id')->nullable();
            $table->index('photo_id', 'ix_32f93cf0dc38776f0b5ac345');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0cfbe96f3c059a12e19beeab');
            $table->index('account_id', 'ix_dfb512f3f425d3aea5f94240');
        });
        Schema::create('tl_input_file_location_input_photo_file_location', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->binary('file_reference')->nullable();
            $table->text('thumb_size')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0c5e614c894206e1a555660c');
            $table->index('account_id', 'ix_6de3c74253b0deeca2621545');
        });
        Schema::create('tl_input_file_location_input_photo_legacy_file_location', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->binary('file_reference')->nullable();
            $table->bigInteger('volume_id')->nullable();
            $table->index('volume_id', 'ix_0eabfdf8465b5edb0cce95f0');
            $table->integer('local_id')->nullable();
            $table->bigInteger('secret')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c5c302ad8b2e00eda4376d9c');
            $table->index('account_id', 'ix_f8790a7a486788b7d5f11318');
        });
        Schema::create('tl_input_file_location_input_secure_file_location', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c79fcd8dafd174d5bd23a683');
            $table->index('account_id', 'ix_f095d64934539167a19f6b57');
        });
        Schema::create('tl_input_file_location_input_sticker_set_thumb', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('stickerset')->nullable();
            $table->index('stickerset', 'ix_ed2d570dc85b348e9762cc9e');
            $table->integer('thumb_version')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bd53fa856c831153a48ed6a7');
            $table->index('account_id', 'ix_5843f25dc1a76bcf2d673d91');
        });
        Schema::create('tl_input_file_location_input_takeout_file_location', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_eeb504f2e9a1ec94edba9f55');
            $table->index('account_id', 'ix_4f804340f31638bacab0f014');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_file_location_input_takeout_file_location');
        Schema::dropIfExists('tl_input_file_location_input_sticker_set_thumb');
        Schema::dropIfExists('tl_input_file_location_input_secure_file_location');
        Schema::dropIfExists('tl_input_file_location_input_photo_legacy_file_location');
        Schema::dropIfExists('tl_input_file_location_input_photo_file_location');
        Schema::dropIfExists('tl_input_file_location_input_peer_photo_file_location');
        Schema::dropIfExists('tl_input_file_location_input_group_call_stream');
        Schema::dropIfExists('tl_input_file_location_input_file_location');
        Schema::dropIfExists('tl_input_file_location_input_encrypted_file_location');
        Schema::dropIfExists('tl_input_file_location_input_document_file_location');
    }
};
