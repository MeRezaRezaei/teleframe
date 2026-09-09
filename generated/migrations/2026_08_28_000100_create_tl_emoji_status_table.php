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
        Schema::create('tl_emoji_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b7a5e5199d3e6e0d8493be35');
            $table->index('account_id', 'ix_4bfee6ad73998eaf19d0663a');
        });
        Schema::create('tl_emoji_status_emoji_status', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('document_id');
            $table->index('document_id', 'ix_a4a5942e33b8aa7063ad6536');
            $table->integer('until')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cbf0061268015737e33739fa');
        });
        Schema::create('tl_emoji_status_emoji_status_collectible', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('collectible_id');
            $table->index('collectible_id', 'ix_1672ac205c1601cf33f36632');
            $table->bigInteger('document_id');
            $table->index('document_id', 'ix_c06afbc1324464eb4d129ab1');
            $table->text('title');
            $table->text('slug');
            $table->bigInteger('pattern_document_id');
            $table->index('pattern_document_id', 'ix_9fd90395ed81ea0c22d32bd8');
            $table->integer('center_color');
            $table->integer('edge_color');
            $table->integer('pattern_color');
            $table->integer('text_color');
            $table->integer('until')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f74805b4a85d34aac375caaa');
        });
        Schema::create('tl_emoji_status_emoji_status_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_status')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a92765dca10f6020240b17b5');
        });
        Schema::create('tl_emoji_status_input_emoji_status_collectible', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('collectible_id');
            $table->index('collectible_id', 'ix_db11a6ec94c1e1fcdd8515c2');
            $table->integer('until')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9d70eee4e01ae05576d885f4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_emoji_status_input_emoji_status_collectible');
        Schema::dropIfExists('tl_emoji_status_emoji_status_empty');
        Schema::dropIfExists('tl_emoji_status_emoji_status_collectible');
        Schema::dropIfExists('tl_emoji_status_emoji_status');
        Schema::dropIfExists('tl_emoji_status');
    }
};
