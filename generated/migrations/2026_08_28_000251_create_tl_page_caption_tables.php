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
        Schema::create('tl_page_caption_page_caption', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_d5b46c07996f3c7c2ea1e67c');
            $table->bigInteger('credit')->nullable();
            $table->index('credit', 'ix_aa4ade5724bff0d3f88e7adf');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5a1e671550e844bf6926a159');
            $table->index('account_id', 'ix_aef555684de71f6130c51edb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_caption_page_caption');
    }
};
