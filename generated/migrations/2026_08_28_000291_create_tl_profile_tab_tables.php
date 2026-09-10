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
        Schema::create('tl_profile_tab_profile_tab_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_53857895c538d37c4cfba7cb');
            $table->index('account_id', 'ix_1edbd0547fe94a07649cf075');
        });
        Schema::create('tl_profile_tab_profile_tab_gifs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8c75289c321ef144a1265094');
            $table->index('account_id', 'ix_a8acbc9621601a26bf806d76');
        });
        Schema::create('tl_profile_tab_profile_tab_gifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8bdc5ab6a3b9fe1e577158d4');
            $table->index('account_id', 'ix_c070ff96c94f0ff06d713675');
        });
        Schema::create('tl_profile_tab_profile_tab_links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_52d3d19dc87485e8dcde90dc');
            $table->index('account_id', 'ix_49994e16a73588dfd0c58725');
        });
        Schema::create('tl_profile_tab_profile_tab_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ea5539920b7d85ee7e4b445f');
            $table->index('account_id', 'ix_ab31e7f79b8d418f5ca799ef');
        });
        Schema::create('tl_profile_tab_profile_tab_music', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d4f1b54e86aa8034792993d8');
            $table->index('account_id', 'ix_275c3e1de505748b42149717');
        });
        Schema::create('tl_profile_tab_profile_tab_posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9371427a16fbe32898ca433b');
            $table->index('account_id', 'ix_77fc718e82f0577285dcc66e');
        });
        Schema::create('tl_profile_tab_profile_tab_voice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ed28513d05d8bbba721c5a74');
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
    }
};
