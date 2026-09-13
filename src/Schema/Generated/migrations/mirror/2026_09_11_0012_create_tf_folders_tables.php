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
        Schema::create('tf_folders', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->integer('id')->unsigned();
        $table->text('title');
        $table->boolean('autofill_new_broadcasts')->default(false);
        $table->boolean('autofill_public_groups')->default(false);
        $table->boolean('autofill_new_correspondents')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_folders_photo', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->boolean('has_video')->default(false);
        $table->bigInteger('photo_id')->unsigned();
        $table->text('stripped_thumb');
        $table->integer('dc_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_folders');

        Schema::dropIfExists('tf_folders_photo');

        Schema::dropIfExists('tf_folders_photo');

        Schema::dropIfExists('tf_folders');

    }
};
