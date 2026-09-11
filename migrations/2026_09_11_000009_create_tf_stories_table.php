<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_stories', function (Blueprint $table) {
            $table->integer('story_id');
            $table->bigInteger('peer_id');
            $table->string('peer_type', 32);
            $table->bigInteger('account_id');
            $table->integer('date')->nullable();
            $table->integer('expire_date')->nullable();
            $table->text('caption')->nullable();
            $table->boolean('is_out')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_close_friends')->default(false);
            $table->boolean('is_edited')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->jsonb('tl_data');
            $table->timestamps();

            $table->primary(['story_id', 'peer_id', 'peer_type', 'account_id']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_stories');
    }
};
