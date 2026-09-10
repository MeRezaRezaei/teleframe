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
        Schema::create('tl_recent_me_url_recent_me_url_chat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('chat_id')->nullable();
            $table->index('chat_id', 'ix_f2c3da7a314dcd9ab147012c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_381cd18a1fec7aec33169780');
            $table->index('account_id', 'ix_7caf15d6eeaa5ce30e5aaf1a');
        });
        Schema::create('tl_recent_me_url_recent_me_url_chat_invite', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('chat_invite')->nullable();
            $table->index('chat_invite', 'ix_97db0b6873583916e7a0e2ab');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c047d5cb1ea28b876862f11f');
            $table->index('account_id', 'ix_cfd436a2d634688e43d67553');
        });
        Schema::create('tl_recent_me_url_recent_me_url_sticker_set', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('set')->nullable();
            $table->index('set', 'ix_ab04c63040818658be68d110');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bff4abd69c1fdfda5e609c9c');
            $table->index('account_id', 'ix_eda4423afc91ba041ab1fc15');
        });
        Schema::create('tl_recent_me_url_recent_me_url_unknown', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_244c139216f13afa4468da07');
            $table->index('account_id', 'ix_5c61984b815211786db38d12');
        });
        Schema::create('tl_recent_me_url_recent_me_url_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_89e417257712032fa59dac8b');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d17bf72ac8a8a2a10a1c720f');
            $table->index('account_id', 'ix_34f85eb39989bc04d3220c98');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_recent_me_url_recent_me_url_user');
        Schema::dropIfExists('tl_recent_me_url_recent_me_url_unknown');
        Schema::dropIfExists('tl_recent_me_url_recent_me_url_sticker_set');
        Schema::dropIfExists('tl_recent_me_url_recent_me_url_chat_invite');
        Schema::dropIfExists('tl_recent_me_url_recent_me_url_chat');
    }
};
