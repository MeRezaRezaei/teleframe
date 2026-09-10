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
        Schema::create('tl_messages_featured_stickers_featured_stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('premium')->default(false);
            $table->bigInteger('hash')->nullable();
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6083be9a5864f2bf3577fea0');
            $table->index('account_id', 'ix_2e81c3c7d97bb2e82ec52a84');
        });
        Schema::create('tl_messages_featured_stickers_featured_stickers__sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_featured_stickers_featured_stickers', 'id', 'fk_0a8dc86421698d3699ac4389')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_99fb8c05f956b245bee0');
            $table->index('account_id', 'ix_2f511b71c0bc4a97b4d8fbca');
        });
        Schema::create('tl_messages_featured_stickers_featured_stickers__unread', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_featured_stickers_featured_stickers', 'id', 'fk_34eb6e772c0631c133e5bf1d')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_64d38fd49843570e20c4');
            $table->index('account_id', 'ix_076b3de06809abeb9bc49940');
        });
        Schema::create('tl_messages_featured_stickers_featured_sticke_5feaa6a0f11a', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2b368ad0dd56a9df4b3884b3');
            $table->index('account_id', 'ix_758c87e77f82531035f3e3c0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_featured_stickers_featured_sticke_5feaa6a0f11a');
        Schema::dropIfExists('tl_messages_featured_stickers_featured_stickers__unread');
        Schema::dropIfExists('tl_messages_featured_stickers_featured_stickers__sets');
        Schema::dropIfExists('tl_messages_featured_stickers_featured_stickers');
    }
};
