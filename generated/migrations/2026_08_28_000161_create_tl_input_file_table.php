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
        Schema::create('tl_input_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e1ec66b5d640baeed8236e60');
            $table->index('account_id', 'ix_abb822cf20a7cf382d598a49');
        });
        Schema::create('tl_input_file_input_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->integer('parts');
            $table->text('name');
            $table->text('md5_checksum');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_521aef92444c6f816b7a77df');
            $table->unique(['account_id', 'tl_id'], 'ux_f9881cfefb70c1d07931');
        });
        Schema::create('tl_input_file_input_file_big', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->integer('parts');
            $table->text('name');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_636059ad0eb489784df43bc9');
            $table->unique(['account_id', 'tl_id'], 'ux_ded87bae3ff60c3d308e');
        });
        Schema::create('tl_input_file_input_file_story_document', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file')->cascadeOnDelete();
            $table->uuid('tl_id');
            $table->index('tl_id', 'ix_bea1f4973daefb8cd88cd2cb');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e221088918a5da6ddf4b0d1b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_file_input_file_story_document');
        Schema::dropIfExists('tl_input_file_input_file_big');
        Schema::dropIfExists('tl_input_file_input_file');
        Schema::dropIfExists('tl_input_file');
    }
};
