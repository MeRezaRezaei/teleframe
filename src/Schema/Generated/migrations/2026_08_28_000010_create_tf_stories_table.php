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
        Schema::create('tf_stories', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->primary('id');
            $table->integer('story_id');
            $table->bigInteger('peer_id');
            $table->bigInteger('constructor_id');
            $table->bigInteger('account_id');
            $table->integer('date')->nullable();
            $table->integer('expire_date')->nullable();
            $table->text('caption')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();
            $table->unique(['peer_id', 'story_id', 'account_id'], 'ux_tf_stories_scope');
            $table->index('account_id', 'ix_tf_stories_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_stories');
    }
};
