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
        Schema::create('tf_web_pages', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('constructor', 64);
            $table->bigInteger('id');
            $table->integer('date');

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_web_pages_url', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->string('constructor', 64)->default(DB::raw("('')"));
            $table->text('url');

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_web_pages')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_web_pages_url');
        Schema::dropIfExists('tf_web_pages');
    }
};
