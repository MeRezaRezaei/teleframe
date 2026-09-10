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
        Schema::create('tl_decrypted_message_decrypted_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('no_webpage')->default(false);
            $table->boolean('silent')->default(false);
            $table->bigInteger('random_id')->nullable();
            $table->index('random_id', 'ix_836755dc23fdbd54eff98468');
            $table->integer('ttl')->nullable();
            $table->text('message')->nullable();
            $table->bigInteger('media')->nullable();
            $table->index('media', 'ix_cb5f0edd94d083f778492c20');
            $table->text('via_bot_name')->nullable();
            $table->bigInteger('reply_to_random_id')->nullable();
            $table->index('reply_to_random_id', 'ix_6405b2bef9d9859cc2e524de');
            $table->bigInteger('grouped_id')->nullable();
            $table->index('grouped_id', 'ix_6a8b6bfacfa70539fcd0b0af');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_384839bfdedf6e18e82200e7');
            $table->index('account_id', 'ix_50c1ba112b3056f2785cf016');
        });
        Schema::create('tl_decrypted_message_decrypted_message__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_fbd070471c0c77fbe118e8e4')->references('id')->on('tl_decrypted_message_decrypted_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9fcd2c13e68aea8bcd7d');
            $table->index('account_id', 'ix_724edb8b571a4ead897f20b2');
        });
        Schema::create('tl_decrypted_message_decrypted_message_service', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('random_id')->nullable();
            $table->index('random_id', 'ix_454888f54218b844635ed29d');
            $table->bigInteger('action')->nullable();
            $table->index('action', 'ix_ee4cd9b38681871b8e47559e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a4ddde7606d5bb293937bb97');
            $table->index('account_id', 'ix_2eb82efae3f298060642bc5d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_decrypted_message_decrypted_message_service');
        Schema::dropIfExists('tl_decrypted_message_decrypted_message__entities');
        Schema::dropIfExists('tl_decrypted_message_decrypted_message');
    }
};
