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
        Schema::create('tl_messages_history_import_parsed_history_import_parsed', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('pm')->default(false);
            $table->boolean('tl_group')->default(false);
            $table->text('title')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_80e7cc9cc52d873f995d0011');
            $table->index('account_id', 'ix_494c29768f115a3c03cd5b9e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_history_import_parsed_history_import_parsed');
    }
};
