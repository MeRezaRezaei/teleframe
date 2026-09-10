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
        Schema::create('tl_messages_dh_config_dh_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('g')->nullable();
            $table->binary('p')->nullable();
            $table->integer('version')->nullable();
            $table->binary('random')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b9cacc6baedfc1e93608321a');
            $table->index('account_id', 'ix_09847590817c9a3a2c4d5996');
        });
        Schema::create('tl_messages_dh_config_dh_config_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('random')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_91eaa9290fe07edd95bf2686');
            $table->index('account_id', 'ix_889ca79282c45f82a4d86dbb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_dh_config_dh_config_not_modified');
        Schema::dropIfExists('tl_messages_dh_config_dh_config');
    }
};
