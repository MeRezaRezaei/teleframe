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
        Schema::create('tl_fragment_collectible_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4ee3fd1dbd272fb8165d3757');
            $table->index('account_id', 'ix_54ea937a17625f444974390d');
        });
        Schema::create('tl_fragment_collectible_info_collectible_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_fragment_collectible_info')->cascadeOnDelete();
            $table->integer('purchase_date');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->text('crypto_currency');
            $table->bigInteger('crypto_amount');
            $table->text('url');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fc7c85348bfda2c94f10688b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_fragment_collectible_info_collectible_info');
        Schema::dropIfExists('tl_fragment_collectible_info');
    }
};
