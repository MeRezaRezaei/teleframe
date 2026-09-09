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
        Schema::create('tl_dc_option', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5bafdc36ef47694ad1aaddfc');
            $table->index('account_id', 'ix_a46fe06fa92e6d85346f6a68');
        });
        Schema::create('tl_dc_option_dc_option', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_dc_option')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('ipv6')->default(false);
            $table->boolean('media_only')->default(false);
            $table->boolean('tcpo_only')->default(false);
            $table->boolean('cdn')->default(false);
            $table->boolean('static')->default(false);
            $table->boolean('this_port_only')->default(false);
            $table->integer('tl_id');
            $table->text('ip_address');
            $table->integer('port');
            $table->binary('secret')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_670d322e18f42554fd2c6ee8');
            $table->unique(['account_id', 'tl_id'], 'ux_fadc168f42eb6e2b1b81');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_dc_option_dc_option');
        Schema::dropIfExists('tl_dc_option');
    }
};
