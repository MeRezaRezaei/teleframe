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
        Schema::create('tf_bot_infos', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('bot_info_id')->unsigned();
        $table->boolean('has_preview_medias')->default(false);
        $table->primary(['account_id', 'bot_info_id']);
        });

        Schema::create('tf_bot_infos_user_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('bot_info_id')->unsigned();
        $table->bigInteger('user_id')->unsigned();
        $table->primary(['account_id', 'bot_info_id']);
        });

        Schema::create('tf_bot_infos_description', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('bot_info_id')->unsigned();
        $table->text('description');
        $table->primary(['account_id', 'bot_info_id']);
        });

        Schema::create('tf_bot_infos_commands', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('bot_info_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('command');
        $table->text('description');
        $table->primary(['account_id', 'bot_info_id', 'position']);
        });

        Schema::create('tf_bot_infos_menu_button', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('bot_info_id')->unsigned();
        $table->text('constructor');
        $table->text('text');
        $table->text('url');
        $table->primary(['account_id', 'bot_info_id']);
        });

        Schema::create('tf_bot_infos_privacy_policy_url', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('bot_info_id')->unsigned();
        $table->text('privacy_policy_url');
        $table->primary(['account_id', 'bot_info_id']);
        });

        Schema::create('tf_bot_infos_app_settings', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('bot_info_id')->unsigned();
        $table->text('placeholder_path');
        $table->integer('background_color')->unsigned();
        $table->integer('background_dark_color')->unsigned();
        $table->integer('header_color')->unsigned();
        $table->integer('header_dark_color')->unsigned();
        $table->primary(['account_id', 'bot_info_id']);
        });

        Schema::create('tf_bot_infos_verifier_settings', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('bot_info_id')->unsigned();
        $table->boolean('can_modify_custom_description')->default(false);
        $table->bigInteger('icon')->unsigned();
        $table->text('company');
        $table->text('custom_description');
        $table->primary(['account_id', 'bot_info_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_bot_infos');

        Schema::dropIfExists('tf_bot_infos_user_id');

        Schema::dropIfExists('tf_bot_infos_description');

        Schema::dropIfExists('tf_bot_infos_commands');

        Schema::dropIfExists('tf_bot_infos_menu_button');

        Schema::dropIfExists('tf_bot_infos_privacy_policy_url');

        Schema::dropIfExists('tf_bot_infos_app_settings');

        Schema::dropIfExists('tf_bot_infos_verifier_settings');

        Schema::dropIfExists('tf_bot_infos_verifier_settings');

        Schema::dropIfExists('tf_bot_infos_app_settings');

        Schema::dropIfExists('tf_bot_infos_privacy_policy_url');

        Schema::dropIfExists('tf_bot_infos_menu_button');

        Schema::dropIfExists('tf_bot_infos_commands');

        Schema::dropIfExists('tf_bot_infos_description');

        Schema::dropIfExists('tf_bot_infos_user_id');

        Schema::dropIfExists('tf_bot_infos');

    }
};
