<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_channels', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->bigInteger('account_id');
            $table->bigInteger('access_hash')->nullable();
            $table->text('title')->nullable();
            $table->string('username', 64)->nullable();
            $table->integer('date')->nullable();
            $table->integer('participants_count')->nullable();
            $table->boolean('is_broadcast')->default(false);
            $table->boolean('is_megagroup')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_restricted')->default(false);
            $table->boolean('is_left')->default(false);
            $table->boolean('is_forum')->default(false);
            $table->boolean('is_creator')->default(false);
            $table->boolean('is_scam')->default(false);
            $table->boolean('is_fake')->default(false);
            $table->boolean('is_noforwards')->default(false);
            $table->boolean('is_gigagroup')->default(false);
            $table->boolean('is_slowmode_enabled')->default(false);
            $table->boolean('is_signatures')->default(false);
            $table->text('restriction_reason')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();

            $table->primary(['id', 'account_id']);
            $table->index('username', 'ix_tf_channels_username')->where('username');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_channels');
    }
};
