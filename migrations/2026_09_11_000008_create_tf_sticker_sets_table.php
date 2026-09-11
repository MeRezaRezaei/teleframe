<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_sticker_sets', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->bigInteger('account_id');
            $table->bigInteger('access_hash')->nullable();
            $table->text('title')->nullable();
            $table->text('short_name')->nullable();
            $table->integer('count')->nullable();
            $table->integer('hash')->nullable();
            $table->boolean('is_official')->default(false);
            $table->boolean('is_masks')->default(false);
            $table->boolean('is_emojis')->default(false);
            $table->integer('installed_date')->nullable();
            $table->bigInteger('thumb_document_id')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();

            $table->primary(['id', 'account_id']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_sticker_sets');
    }
};
