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
        Schema::create('tf_message_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('message_entity_id')->unsigned();
        $table->text('constructor');
        $table->integer('offset')->unsigned();
        $table->integer('length')->unsigned();
        $table->primary(['account_id', 'message_entity_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_message_entities');

        Schema::dropIfExists('tf_message_entities');

    }
};
