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
        Schema::create('tl_account_connected_bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ad2973270e9ccf62793da6d5');
            $table->index('account_id', 'ix_0a0aae20d22575bc1530d16b');
        });
        Schema::create('tl_account_connected_bots_connected_bots', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_connected_bots')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b42eedeccec9ebc67f672ec2');
        });
        Schema::create('tl_account_connected_bots_connected_bots__connected_bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_connected_bots_connected_bots')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_871789b727e77448c566');
            $table->index('account_id', 'ix_ff8153f2dc36df19ef959716');
        });
        Schema::create('tl_account_connected_bots_connected_bots__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_connected_bots_connected_bots')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e1f2c70b5f2895d6698e');
            $table->index('account_id', 'ix_c068c1748d4a0f8f53246165');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_connected_bots_connected_bots__users');
        Schema::dropIfExists('tl_account_connected_bots_connected_bots__connected_bots');
        Schema::dropIfExists('tl_account_connected_bots_connected_bots');
        Schema::dropIfExists('tl_account_connected_bots');
    }
};
