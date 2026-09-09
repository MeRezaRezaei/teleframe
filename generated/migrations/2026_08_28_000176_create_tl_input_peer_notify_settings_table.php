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
        Schema::create('tl_input_peer_notify_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_2fe17f964ec98662746ae3ab');
            $table->index('account_id', 'ix_e7ba51073c41b441ab630d61');
        });
        Schema::create('tl_input_peer_notify_settings_input_peer_notify_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer_notify_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('show_previews')->nullable();
            $table->index('show_previews', 'ix_82ab8f2d62ea3450eca99eb1');
            $table->uuid('silent')->nullable();
            $table->index('silent', 'ix_53bdc78114afa1ab4cdb2ed9');
            $table->integer('mute_until')->nullable();
            $table->uuid('sound')->nullable();
            $table->index('sound', 'ix_dfe7fc10a32d55902f19f0a2');
            $table->uuid('stories_muted')->nullable();
            $table->index('stories_muted', 'ix_b4e67cc386296e3c545eea9a');
            $table->uuid('stories_hide_sender')->nullable();
            $table->index('stories_hide_sender', 'ix_0c8909da5c76ccdf5a4ea481');
            $table->uuid('stories_sound')->nullable();
            $table->index('stories_sound', 'ix_cd6dbc7eca78d564f3e827bd');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5efa2b717bdd95120effd03a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_peer_notify_settings_input_peer_notify_settings');
        Schema::dropIfExists('tl_input_peer_notify_settings');
    }
};
