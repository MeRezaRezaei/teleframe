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
        Schema::create('tf_phone_calls', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('date')->unsigned();
        $table->bigInteger('admin_id')->unsigned();
        $table->bigInteger('participant_id')->unsigned();
        $table->boolean('video')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_phone_calls_protocol', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('udp_p2p')->default(false);
        $table->boolean('udp_reflector')->default(false);
        $table->integer('min_layer')->unsigned();
        $table->integer('max_layer')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_phone_calls_protocol_library_versions', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('value');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_phone_calls_receive_date', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('receive_date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_phone_calls');

        Schema::dropIfExists('tf_phone_calls_protocol');

        Schema::dropIfExists('tf_phone_calls_protocol_library_versions');

        Schema::dropIfExists('tf_phone_calls_receive_date');

        Schema::dropIfExists('tf_phone_calls_receive_date');

        Schema::dropIfExists('tf_phone_calls_protocol_library_versions');

        Schema::dropIfExists('tf_phone_calls_protocol');

        Schema::dropIfExists('tf_phone_calls');

    }
};
