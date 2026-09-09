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
        Schema::create('tl_profile_tab', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4988ca0627123a9543de3ffb');
            $table->index('account_id', 'ix_836e0395f99afe0fbea23ca6');
        });
        Schema::create('tl_profile_tab_profile_tab_files', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1edbd0547fe94a07649cf075');
        });
        Schema::create('tl_profile_tab_profile_tab_gifs', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a8acbc9621601a26bf806d76');
        });
        Schema::create('tl_profile_tab_profile_tab_gifts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c070ff96c94f0ff06d713675');
        });
        Schema::create('tl_profile_tab_profile_tab_links', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_49994e16a73588dfd0c58725');
        });
        Schema::create('tl_profile_tab_profile_tab_media', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ab31e7f79b8d418f5ca799ef');
        });
        Schema::create('tl_profile_tab_profile_tab_music', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_275c3e1de505748b42149717');
        });
        Schema::create('tl_profile_tab_profile_tab_posts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_77fc718e82f0577285dcc66e');
        });
        Schema::create('tl_profile_tab_profile_tab_voice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_41105cec738eac51497ae765');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_profile_tab_profile_tab_voice');
        Schema::dropIfExists('tl_profile_tab_profile_tab_posts');
        Schema::dropIfExists('tl_profile_tab_profile_tab_music');
        Schema::dropIfExists('tl_profile_tab_profile_tab_media');
        Schema::dropIfExists('tl_profile_tab_profile_tab_links');
        Schema::dropIfExists('tl_profile_tab_profile_tab_gifts');
        Schema::dropIfExists('tl_profile_tab_profile_tab_gifs');
        Schema::dropIfExists('tl_profile_tab_profile_tab_files');
        Schema::dropIfExists('tl_profile_tab');
    }
};
