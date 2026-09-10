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
        Schema::create('tl_dialog_peer_dialog_peer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_49ef885999b1f436edc3f90d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ec25a5fe04b20c104360683a');
            $table->index('account_id', 'ix_749442336981a19e91a1fcaf');
        });
        Schema::create('tl_dialog_peer_dialog_peer_folder', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('folder_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_958ed0f05b0cc718febc1c30');
            $table->index('account_id', 'ix_ce670b6e57e803f36842630a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_dialog_peer_dialog_peer_folder');
        Schema::dropIfExists('tl_dialog_peer_dialog_peer');
    }
};
