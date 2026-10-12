<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TStore truth tables (verbatim S5/S3, plan W3): the local projection of
 * what Teleframe knows about its managed buckets, objects, and indexed
 * media. tf_store_buckets is the local truth for channel identity
 * (channel_id/access_hash per managed prefix — store/data/backup);
 * tf_store_objects is the object index (name, sealed/raw, chunk plan,
 * metadata); tf_store_media is the S3 media hash index (Telegram-native
 * doc identity + our chunk sha256, content sha256 lazy). No timestamps —
 * state projection, same convention as the mirror tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_store_buckets', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('bucket', 255);
            $table->string('kind', 16); // store|data|backup
            $table->bigInteger('channel_id');
            $table->bigInteger('access_hash');
            $table->integer('created_at_unix');

            $table->primary(['account_id', 'bucket']);
        });

        Schema::create('tf_store_objects', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('bucket', 255);
            $table->string('name', 255);
            $table->string('mode', 16); // sealed|raw
            $table->bigInteger('size');
            $table->text('chunks'); // JSON chunk plan (n, offset, size, sha256[])
            $table->text('metadata'); // TFSTORE1 pretty JSON payload
            $table->integer('created_at_unix');
            $table->integer('updated_at_unix');

            $table->primary(['account_id', 'bucket', 'name']);
            $table->foreign(['account_id', 'bucket'])
                ->references(['account_id', 'bucket'])
                ->on('tf_store_buckets')
                ->onDelete('cascade');
        });

        Schema::create('tf_store_media', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('bucket', 255);
            $table->bigInteger('msg_id');
            $table->string('media_type', 32);
            $table->bigInteger('doc_id');
            $table->bigInteger('access_hash');
            $table->text('file_reference');
            $table->bigInteger('size');
            $table->string('mime', 191);
            $table->string('content_sha256', 64)->nullable();
            $table->integer('indexed_at_unix');

            $table->primary(['account_id', 'bucket', 'msg_id']);
            $table->foreign(['account_id', 'bucket'])
                ->references(['account_id', 'bucket'])
                ->on('tf_store_buckets')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_store_media');
        Schema::dropIfExists('tf_store_objects');
        Schema::dropIfExists('tf_store_buckets');
    }
};
