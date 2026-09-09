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
        Schema::create('tl_rich_text', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_46489ab41c92ac4a4182ea17');
            $table->index('account_id', 'ix_f79acf6330b2ce9fb7d07f89');
        });
        Schema::create('tl_rich_text_text_anchor', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_f11780508ba17dc12a58b2cc');
            $table->text('name');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9b99b10ade5f9f168d4b78a8');
        });
        Schema::create('tl_rich_text_text_auto_email', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_8bac0a97be27d6c62152f503');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_406f24e929a27cf193c69776');
        });
        Schema::create('tl_rich_text_text_auto_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_e7ba13beda0326246e69fd81');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_df21962177e6d71f6ccc30c2');
        });
        Schema::create('tl_rich_text_text_auto_url', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_3bd60abd230b4a9a754624ba');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5891c19b9c1e4673205a0fa0');
        });
        Schema::create('tl_rich_text_text_bank_card', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_aaedc2405fe0b8e04e3f3607');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_edb1f548d24ecf56503ba3e6');
        });
        Schema::create('tl_rich_text_text_bold', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_7142fa3a7af28f840ed4dc95');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_71e198d1ad57e5c3b2c1de81');
        });
        Schema::create('tl_rich_text_text_bot_command', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_9f64ba840631ce17033aa5e8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a7f36518ec5d4579c6680835');
        });
        Schema::create('tl_rich_text_text_cashtag', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_a10ee37d235ea265b6640788');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_eba8aa1bb48a2ebdbcff58c3');
        });
        Schema::create('tl_rich_text_text_concat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9b5b70a4f5c50802c514465f');
        });
        Schema::create('tl_rich_text_text_concat__texts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_rich_text_text_concat')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_aafca14d0b43526f10ca');
            $table->index('account_id', 'ix_1aebd3e7fa44ea43c49d622a');
        });
        Schema::create('tl_rich_text_text_custom_emoji', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->bigInteger('document_id');
            $table->index('document_id', 'ix_9878430b44e19b6df13996c7');
            $table->text('alt');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7f06e370f91ee8c63abbee4f');
        });
        Schema::create('tl_rich_text_text_date', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('relative')->default(false);
            $table->boolean('short_time')->default(false);
            $table->boolean('long_time')->default(false);
            $table->boolean('short_date')->default(false);
            $table->boolean('long_date')->default(false);
            $table->boolean('day_of_week')->default(false);
            $table->uuid('text');
            $table->index('text', 'ix_8d6532d442c8d76575845dc7');
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f58595960cdc440e6d40ef1b');
        });
        Schema::create('tl_rich_text_text_email', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_7d7f8366e094f71a7f2d136f');
            $table->text('email');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_21a4498f45cc9b4901e3ac1f');
        });
        Schema::create('tl_rich_text_text_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7694e3beadfa66d4bba22905');
        });
        Schema::create('tl_rich_text_text_fixed', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_97393df395657100a55bd25f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_10f8fdf1ae4afe432789c7d3');
        });
        Schema::create('tl_rich_text_text_hashtag', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_524aedd2f8f60df24145bbb3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_489c35a3cf7fb7c5679d827f');
        });
        Schema::create('tl_rich_text_text_image', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->bigInteger('document_id');
            $table->index('document_id', 'ix_68bae00b459aa6d14e4d3961');
            $table->integer('w');
            $table->integer('h');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1c4df92f43e06fe1455ece7e');
        });
        Schema::create('tl_rich_text_text_italic', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_b3b0cfb9b97fb644d3c0a8f9');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3a08717611ada8f599627ea4');
        });
        Schema::create('tl_rich_text_text_marked', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_97adb9eb63b1f181d4bd2dc8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2e0788385dee30ee7f6359d3');
        });
        Schema::create('tl_rich_text_text_math', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->text('source');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_939eb00e3a69b4384501cdcd');
        });
        Schema::create('tl_rich_text_text_mention', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_4afccf9c9528edcea79f8f21');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c8a1903c69dfb1f0c9a818d0');
        });
        Schema::create('tl_rich_text_text_mention_name', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_84b0f3096fe2d9bae416661e');
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_25ea498675a1427a77a62aed');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fd1a65160793e4183a79c878');
        });
        Schema::create('tl_rich_text_text_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_5d5529aa37bc405bce3e0682');
            $table->text('phone');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_21cb39d69bbdb07c14c17f8b');
        });
        Schema::create('tl_rich_text_text_plain', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->text('text');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_05cd843194598c04e491e914');
        });
        Schema::create('tl_rich_text_text_spoiler', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_83ead6f0faaae2f75bcb2c3f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d5bc8d70a5596faf619b643a');
        });
        Schema::create('tl_rich_text_text_strike', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_b9de23300170f3f1db7ace1c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_82d22547d81d22f8aeb2ca21');
        });
        Schema::create('tl_rich_text_text_subscript', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_47f6d3c3ad2491a54222b97a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_073cae9a8687fec6579555b2');
        });
        Schema::create('tl_rich_text_text_superscript', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_4b816c7487c11e5f4938dd43');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6c0c8605c2b26dd4b3c4e200');
        });
        Schema::create('tl_rich_text_text_underline', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_139af1c7848bb46af5bc40bb');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_52c99b464b345601648eb7dc');
        });
        Schema::create('tl_rich_text_text_url', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_c984b872e0039664a18483ee');
            $table->text('url');
            $table->bigInteger('webpage_id');
            $table->index('webpage_id', 'ix_41d0d38d2360d53456c79b1e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bfa7bed0088f73bfc6117a36');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_rich_text_text_url');
        Schema::dropIfExists('tl_rich_text_text_underline');
        Schema::dropIfExists('tl_rich_text_text_superscript');
        Schema::dropIfExists('tl_rich_text_text_subscript');
        Schema::dropIfExists('tl_rich_text_text_strike');
        Schema::dropIfExists('tl_rich_text_text_spoiler');
        Schema::dropIfExists('tl_rich_text_text_plain');
        Schema::dropIfExists('tl_rich_text_text_phone');
        Schema::dropIfExists('tl_rich_text_text_mention_name');
        Schema::dropIfExists('tl_rich_text_text_mention');
        Schema::dropIfExists('tl_rich_text_text_math');
        Schema::dropIfExists('tl_rich_text_text_marked');
        Schema::dropIfExists('tl_rich_text_text_italic');
        Schema::dropIfExists('tl_rich_text_text_image');
        Schema::dropIfExists('tl_rich_text_text_hashtag');
        Schema::dropIfExists('tl_rich_text_text_fixed');
        Schema::dropIfExists('tl_rich_text_text_empty');
        Schema::dropIfExists('tl_rich_text_text_email');
        Schema::dropIfExists('tl_rich_text_text_date');
        Schema::dropIfExists('tl_rich_text_text_custom_emoji');
        Schema::dropIfExists('tl_rich_text_text_concat__texts');
        Schema::dropIfExists('tl_rich_text_text_concat');
        Schema::dropIfExists('tl_rich_text_text_cashtag');
        Schema::dropIfExists('tl_rich_text_text_bot_command');
        Schema::dropIfExists('tl_rich_text_text_bold');
        Schema::dropIfExists('tl_rich_text_text_bank_card');
        Schema::dropIfExists('tl_rich_text_text_auto_url');
        Schema::dropIfExists('tl_rich_text_text_auto_phone');
        Schema::dropIfExists('tl_rich_text_text_auto_email');
        Schema::dropIfExists('tl_rich_text_text_anchor');
        Schema::dropIfExists('tl_rich_text');
    }
};
