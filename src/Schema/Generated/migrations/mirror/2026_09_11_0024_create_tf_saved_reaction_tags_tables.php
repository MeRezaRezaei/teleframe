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
        Schema::create('tf_saved_reaction_tags', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_reaction_tag_id')->unsigned();
        $table->integer('count')->unsigned();
        $table->primary(['account_id', 'saved_reaction_tag_id']);
        });

        Schema::create('tf_saved_reaction_tags_title', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_reaction_tag_id')->unsigned();
        $table->text('title');
        $table->primary(['account_id', 'saved_reaction_tag_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_saved_reaction_tags');

        Schema::dropIfExists('tf_saved_reaction_tags_title');

        Schema::dropIfExists('tf_saved_reaction_tags_title');

        Schema::dropIfExists('tf_saved_reaction_tags');

    }
};
