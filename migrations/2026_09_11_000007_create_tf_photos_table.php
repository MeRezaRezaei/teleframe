<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_photos', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->bigInteger('account_id');
            $table->bigInteger('access_hash')->nullable();
            $table->integer('date')->nullable();
            $table->integer('dc_id')->nullable();
            $table->boolean('has_stickers')->default(false);
            $table->binary('file_reference')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();

            $table->primary(['id', 'account_id']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_photos');
    }
};
