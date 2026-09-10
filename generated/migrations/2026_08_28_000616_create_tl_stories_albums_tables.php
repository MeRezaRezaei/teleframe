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
        Schema::create('tl_stories_albums_albums', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_68e854a856be814ed080cf9e');
            $table->index('account_id', 'ix_999d9f00eb34f2c6d1f5f3cd');
        });
        Schema::create('tl_stories_albums_albums__albums', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_stories_albums_albums', 'id', 'fk_a678f061f06ef8019df354d0')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0624bc3defede46a54d0');
            $table->index('account_id', 'ix_68b25644bb71194863f8144f');
        });
        Schema::create('tl_stories_albums_albums_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_05bcfb0273b1646aba668552');
            $table->index('account_id', 'ix_b125c14e03e284d306a0accf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_albums_albums_not_modified');
        Schema::dropIfExists('tl_stories_albums_albums__albums');
        Schema::dropIfExists('tl_stories_albums_albums');
    }
};
