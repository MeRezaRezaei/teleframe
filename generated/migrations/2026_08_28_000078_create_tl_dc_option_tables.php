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
        Schema::create('tl_dc_option_dc_option', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('ipv6')->default(false);
            $table->boolean('media_only')->default(false);
            $table->boolean('tcpo_only')->default(false);
            $table->boolean('cdn')->default(false);
            $table->boolean('static')->default(false);
            $table->boolean('this_port_only')->default(false);
            $table->integer('tl_id')->nullable();
            $table->text('ip_address')->nullable();
            $table->integer('port')->nullable();
            $table->binary('secret')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8df226c41f25009b50529ca4');
            $table->index('account_id', 'ix_670d322e18f42554fd2c6ee8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_dc_option_dc_option');
    }
};
