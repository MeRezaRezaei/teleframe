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
        Schema::create('tl_bank_card_open_url_bank_card_open_url', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->text('name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d69d6cb9eb1a43c73117d49c');
            $table->index('account_id', 'ix_c6002e41fc488dc138218fba');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bank_card_open_url_bank_card_open_url');
    }
};
