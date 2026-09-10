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
        Schema::create('tl_messages_forum_topics_forum_topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('order_by_create_date')->default(false);
            $table->integer('count')->nullable();
            $table->integer('pts')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_34bffb3e5e6f3391de3a3302');
            $table->index('account_id', 'ix_040b6b035a748b43f8166019');
        });
        Schema::create('tl_messages_forum_topics_forum_topics__topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_forum_topics_forum_topics', 'id', 'fk_da0c717711462051bd6e3d4c')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6b2d645185200386d191');
            $table->index('account_id', 'ix_5d2a0725a8ceaabb69e54b9b');
        });
        Schema::create('tl_messages_forum_topics_forum_topics__messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_forum_topics_forum_topics', 'id', 'fk_a2103b7473eeba0f820a9458')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_061dc5ad011c1efcec9d');
            $table->index('account_id', 'ix_737d3f502a40de38952bbf7a');
        });
        Schema::create('tl_messages_forum_topics_forum_topics__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_forum_topics_forum_topics', 'id', 'fk_669b507222efd5fd76930e92')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b373bdfc0ca2e9246d90');
            $table->index('account_id', 'ix_100caf06a99aa80380e2e748');
        });
        Schema::create('tl_messages_forum_topics_forum_topics__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_forum_topics_forum_topics', 'id', 'fk_0f9bfeab0f2492dfc26adfff')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_66d1bff450ffac14b475');
            $table->index('account_id', 'ix_204cf69e76ead3175db09cd9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_forum_topics_forum_topics__users');
        Schema::dropIfExists('tl_messages_forum_topics_forum_topics__chats');
        Schema::dropIfExists('tl_messages_forum_topics_forum_topics__messages');
        Schema::dropIfExists('tl_messages_forum_topics_forum_topics__topics');
        Schema::dropIfExists('tl_messages_forum_topics_forum_topics');
    }
};
