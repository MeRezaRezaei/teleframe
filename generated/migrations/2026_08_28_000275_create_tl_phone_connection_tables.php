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
        Schema::create('tl_phone_connection_phone_connection', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('tcp')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->text('ip')->nullable();
            $table->text('ipv6')->nullable();
            $table->integer('port')->nullable();
            $table->binary('peer_tag')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_00a32179273c184e1052edfd');
            $table->index('account_id', 'ix_acbc9804cc446039234e5dd7');
        });
        Schema::create('tl_phone_connection_phone_connection_webrtc', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('turn')->default(false);
            $table->boolean('stun')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->text('ip')->nullable();
            $table->text('ipv6')->nullable();
            $table->integer('port')->nullable();
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_32c37a948a2e3525bd98ab28');
            $table->index('account_id', 'ix_6f86f0b13eae0e91c029fe82');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_connection_phone_connection_webrtc');
        Schema::dropIfExists('tl_phone_connection_phone_connection');
    }
};
