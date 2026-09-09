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
        Schema::create('tl_peer_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c17ce916d932b7ec52064ee7');
            $table->index('account_id', 'ix_4ee993240dd3c5fe479876ee');
        });
        Schema::create('tl_peer_stories_peer_stories', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer_stories')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_c99290ee7670057f589159bd');
            $table->integer('max_read_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_093675cafabb59b2b30be27e');
        });
        Schema::create('tl_peer_stories_peer_stories__stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_peer_stories_peer_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_91b33e4d9d8ccbbfb49d');
            $table->index('account_id', 'ix_e751a85f5a3a0579771e1ea7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_stories_peer_stories__stories');
        Schema::dropIfExists('tl_peer_stories_peer_stories');
        Schema::dropIfExists('tl_peer_stories');
    }
};
