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
        Schema::create('tf_themes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('access_hash')->unsigned();
        $table->text('slug');
        $table->text('title');
        $table->boolean('creator')->default(false);
        $table->boolean('default')->default(false);
        $table->boolean('for_chat')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_themes_settings', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->boolean('message_colors_animated')->default(false);
        $table->integer('accent_color')->unsigned();
        $table->integer('outbox_accent_color')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_themes_settings_base_theme', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_themes_settings_message_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_themes_emoticon', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('emoticon');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_themes_installs_count', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('installs_count')->unsigned();
        $table->primary(['account_id', 'id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_themes');

        Schema::dropIfExists('tf_themes_settings');

        Schema::dropIfExists('tf_themes_settings_base_theme');

        Schema::dropIfExists('tf_themes_settings_message_colors');

        Schema::dropIfExists('tf_themes_emoticon');

        Schema::dropIfExists('tf_themes_installs_count');

        Schema::dropIfExists('tf_themes_installs_count');

        Schema::dropIfExists('tf_themes_emoticon');

        Schema::dropIfExists('tf_themes_settings_message_colors');

        Schema::dropIfExists('tf_themes_settings_base_theme');

        Schema::dropIfExists('tf_themes_settings');

        Schema::dropIfExists('tf_themes');

    }
};
