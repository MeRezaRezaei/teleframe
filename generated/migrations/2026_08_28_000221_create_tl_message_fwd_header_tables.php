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
        Schema::create('tl_message_fwd_header_message_fwd_header', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('imported')->default(false);
            $table->boolean('saved_out')->default(false);
            $table->bigInteger('from_id')->nullable();
            $table->index('from_id', 'ix_fcffd0624390dbccada9d5d4');
            $table->text('from_name')->nullable();
            $table->integer('date')->nullable();
            $table->integer('channel_post')->nullable();
            $table->text('post_author')->nullable();
            $table->bigInteger('saved_from_peer')->nullable();
            $table->index('saved_from_peer', 'ix_3a0bb4ed5d5ab2fc0bda37b5');
            $table->integer('saved_from_msg_id')->nullable();
            $table->bigInteger('saved_from_id')->nullable();
            $table->index('saved_from_id', 'ix_ba0a3d4cddf5069450ba4469');
            $table->text('saved_from_name')->nullable();
            $table->integer('saved_date')->nullable();
            $table->text('psa_type')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fbc5feafce8559c038ae1875');
            $table->index('account_id', 'ix_7051ff8fc7963817da73d89d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_fwd_header_message_fwd_header');
    }
};
