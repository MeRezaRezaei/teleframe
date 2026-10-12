<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_photos', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('constructor', 64);
            $table->bigInteger('id');
            $table->bigInteger('access_hash');
            $table->text('file_reference');
            $table->integer('date');
            $table->integer('dc_id');
            $table->boolean('has_stickers')->default(false);

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_photos_sizes', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->smallInteger('position');
            $table->string('constructor', 64)->default(DB::raw("('')"));
            $table->string('type', 64);
            $table->integer('w')->default(0);
            $table->integer('h')->default(0);
            $table->integer('size')->default(0);
            $table->string('bytes', 2048)->default(DB::raw("('')"));

            $table->primary(['account_id', 'id', 'position']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_photos')
                ->onDelete('cascade');
        });

        Schema::create('tf_photos_video_sizes', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->smallInteger('position');
            $table->string('constructor', 64)->default(DB::raw("('')"));
            $table->string('type', 255)->default(DB::raw("('')"));
            $table->integer('w')->default(0);
            $table->integer('h')->default(0);
            $table->integer('size')->default(0);
            $table->double('video_start_ts')->default(0);
            $table->bigInteger('emoji_id')->default(0);
            $table->bigInteger('sticker_id')->default(0);

            $table->primary(['account_id', 'id', 'position']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_photos')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_photos_video_sizes');
        Schema::dropIfExists('tf_photos_sizes');
        Schema::dropIfExists('tf_photos');
    }
};
