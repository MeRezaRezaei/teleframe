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
        Schema::create('tl_payment_saved_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ad72efa6245fa8c8ceca2e7b');
            $table->index('account_id', 'ix_1007a67f9dcae82471a84976');
        });
        Schema::create('tl_payment_saved_credentials_payment_saved_cr_5362dcf43125', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payment_saved_credentials')->cascadeOnDelete();
            $table->text('tl_id');
            $table->text('title');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4e105d16962c738c16f5278b');
            $table->unique(['account_id', 'tl_id'], 'ux_6273092f8607d3d8f37d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payment_saved_credentials_payment_saved_cr_5362dcf43125');
        Schema::dropIfExists('tl_payment_saved_credentials');
    }
};
