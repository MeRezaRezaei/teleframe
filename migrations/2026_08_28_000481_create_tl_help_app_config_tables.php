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
        Schema::create('tl_help_app_config_app_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('hash')->nullable();
            $table->bigInteger('config')->nullable();
            $table->index('config', 'ix_468b2196623818496026fff7');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d75870fb4c10a8e30ccd52fe');
            $table->index('account_id', 'ix_a41a344bf0a886a70f20ba40');
        });
        Schema::create('tl_help_app_config_app_config_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_60e4e5d5d6bee71dd0311b79');
            $table->index('account_id', 'ix_22904b166f87b85a133e7e71');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_app_config_app_config_not_modified');
        Schema::dropIfExists('tl_help_app_config_app_config');
    }
};
