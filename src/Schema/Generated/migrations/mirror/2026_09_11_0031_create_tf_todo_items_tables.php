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
        Schema::create('tf_todo_items', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->integer('id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_todo_items_title', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('text');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_todo_items_title_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('offset')->unsigned();
        $table->integer('length')->unsigned();
        $table->text('language');
        $table->text('url');
        $table->bigInteger('user_id')->unsigned();
        $table->bigInteger('document_id')->unsigned();
        $table->boolean('collapsed')->default(false);
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->text('old_text');
        $table->primary(['account_id', 'id', 'position']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_todo_items');

        Schema::dropIfExists('tf_todo_items_title');

        Schema::dropIfExists('tf_todo_items_title_entities');

        Schema::dropIfExists('tf_todo_items_title_entities');

        Schema::dropIfExists('tf_todo_items_title');

        Schema::dropIfExists('tf_todo_items');

    }
};
