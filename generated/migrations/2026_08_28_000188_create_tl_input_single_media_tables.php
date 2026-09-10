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
        Schema::create('tl_input_single_media_input_single_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('media')->nullable();
            $table->index('media', 'ix_38f2db8daad9b06d29927473');
            $table->bigInteger('random_id')->nullable();
            $table->index('random_id', 'ix_19b05baef7e8c6bfcd4babd9');
            $table->text('message')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_42c28f3736e201eb08c41d14');
            $table->index('account_id', 'ix_16cf648a6aac777df5443c93');
        });
        Schema::create('tl_input_single_media_input_single_media__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_d7b6e41494cd32652ab78248')->references('id')->on('tl_input_single_media_input_single_media')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_70e5d717b1880ad3bef7');
            $table->index('account_id', 'ix_949532d4c3d4b739b416fee9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_single_media_input_single_media__entities');
        Schema::dropIfExists('tl_input_single_media_input_single_media');
    }
};
