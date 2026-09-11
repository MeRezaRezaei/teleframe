<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_wallpapers', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->bigInteger('account_id');
            $table->bigInteger('access_hash')->nullable();
            $table->text('title')->nullable();
            $table->text('slug')->nullable();
            $table->bigInteger('document_id')->nullable();
            $table->boolean('is_creator')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_pattern')->default(false);
            $table->boolean('is_dark')->default(false);
            $table->jsonb('tl_data');
            $table->timestamps();

            $table->primary(['id', 'account_id']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_wallpapers');
    }
};
