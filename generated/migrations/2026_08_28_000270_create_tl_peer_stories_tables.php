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
        Schema::create('tl_peer_stories_peer_stories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_c99290ee7670057f589159bd');
            $table->integer('max_read_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2fa71528a02bf0b2306642e7');
            $table->index('account_id', 'ix_093675cafabb59b2b30be27e');
        });
        Schema::create('tl_peer_stories_peer_stories__stories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_peer_stories_peer_stories', 'id', 'fk_d58d45710e522c73e85572ae')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_91b33e4d9d8ccbbfb49d');
            $table->index('account_id', 'ix_e751a85f5a3a0579771e1ea7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_stories_peer_stories__stories');
        Schema::dropIfExists('tl_peer_stories_peer_stories');
    }
};
