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
        Schema::create('tf_business_chat_links', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('business_chat_link_id')->unsigned();
        $table->text('link');
        $table->text('message');
        $table->integer('views')->unsigned();
        $table->primary(['account_id', 'business_chat_link_id']);
        });

        Schema::create('tf_business_chat_links_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('business_chat_link_id')->unsigned();
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
        $table->primary(['account_id', 'business_chat_link_id', 'position']);
        });

        Schema::create('tf_business_chat_links_title', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('business_chat_link_id')->unsigned();
        $table->text('title');
        $table->primary(['account_id', 'business_chat_link_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_business_chat_links');

        Schema::dropIfExists('tf_business_chat_links_entities');

        Schema::dropIfExists('tf_business_chat_links_title');

        Schema::dropIfExists('tf_business_chat_links_title');

        Schema::dropIfExists('tf_business_chat_links_entities');

        Schema::dropIfExists('tf_business_chat_links');

    }
};
