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
        Schema::create('tl_emoji_status_emoji_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('document_id')->nullable();
            $table->index('document_id', 'ix_a4a5942e33b8aa7063ad6536');
            $table->integer('until')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_57640798509e4447d10ad0f0');
            $table->index('account_id', 'ix_cbf0061268015737e33739fa');
        });
        Schema::create('tl_emoji_status_emoji_status_collectible', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('collectible_id')->nullable();
            $table->index('collectible_id', 'ix_1672ac205c1601cf33f36632');
            $table->bigInteger('document_id')->nullable();
            $table->index('document_id', 'ix_c06afbc1324464eb4d129ab1');
            $table->text('title')->nullable();
            $table->text('slug')->nullable();
            $table->bigInteger('pattern_document_id')->nullable();
            $table->index('pattern_document_id', 'ix_9fd90395ed81ea0c22d32bd8');
            $table->integer('center_color')->nullable();
            $table->integer('edge_color')->nullable();
            $table->integer('pattern_color')->nullable();
            $table->integer('text_color')->nullable();
            $table->integer('until')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c4591f978b076a88fb94c964');
            $table->index('account_id', 'ix_f74805b4a85d34aac375caaa');
        });
        Schema::create('tl_emoji_status_emoji_status_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6732a4b5f9b2436a61b02876');
            $table->index('account_id', 'ix_a92765dca10f6020240b17b5');
        });
        Schema::create('tl_emoji_status_input_emoji_status_collectible', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('collectible_id')->nullable();
            $table->index('collectible_id', 'ix_db11a6ec94c1e1fcdd8515c2');
            $table->integer('until')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7c9d551d2e6616a473c389d7');
            $table->index('account_id', 'ix_9d70eee4e01ae05576d885f4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_emoji_status_input_emoji_status_collectible');
        Schema::dropIfExists('tl_emoji_status_emoji_status_empty');
        Schema::dropIfExists('tl_emoji_status_emoji_status_collectible');
        Schema::dropIfExists('tl_emoji_status_emoji_status');
    }
};
