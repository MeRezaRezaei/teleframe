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
        Schema::create('tl_rich_text_text_anchor', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_f11780508ba17dc12a58b2cc');
            $table->text('name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6f93b582dafaa8b935207453');
            $table->index('account_id', 'ix_9b99b10ade5f9f168d4b78a8');
        });
        Schema::create('tl_rich_text_text_auto_email', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_8bac0a97be27d6c62152f503');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6c7c2760e633534a265f6d85');
            $table->index('account_id', 'ix_406f24e929a27cf193c69776');
        });
        Schema::create('tl_rich_text_text_auto_phone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_e7ba13beda0326246e69fd81');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_887a784b7b9b5b65911764c8');
            $table->index('account_id', 'ix_df21962177e6d71f6ccc30c2');
        });
        Schema::create('tl_rich_text_text_auto_url', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_3bd60abd230b4a9a754624ba');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ecd700eb769b9430d6ddfe1d');
            $table->index('account_id', 'ix_5891c19b9c1e4673205a0fa0');
        });
        Schema::create('tl_rich_text_text_bank_card', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_aaedc2405fe0b8e04e3f3607');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c0e5c7202c13b290a15e714d');
            $table->index('account_id', 'ix_edb1f548d24ecf56503ba3e6');
        });
        Schema::create('tl_rich_text_text_bold', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_7142fa3a7af28f840ed4dc95');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5d70154c01bb6d2da5935516');
            $table->index('account_id', 'ix_71e198d1ad57e5c3b2c1de81');
        });
        Schema::create('tl_rich_text_text_bot_command', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_9f64ba840631ce17033aa5e8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8956d04919313d711e5b9c5a');
            $table->index('account_id', 'ix_a7f36518ec5d4579c6680835');
        });
        Schema::create('tl_rich_text_text_cashtag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_a10ee37d235ea265b6640788');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bf1bf9fc7c25b7df1dde7937');
            $table->index('account_id', 'ix_eba8aa1bb48a2ebdbcff58c3');
        });
        Schema::create('tl_rich_text_text_concat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3c05c41d520806c6e7cd0d54');
            $table->index('account_id', 'ix_9b5b70a4f5c50802c514465f');
        });
        Schema::create('tl_rich_text_text_concat__texts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_rich_text_text_concat', 'id', 'fk_92ee50009f3f5cc317bbc490')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_aafca14d0b43526f10ca');
            $table->index('account_id', 'ix_1aebd3e7fa44ea43c49d622a');
        });
        Schema::create('tl_rich_text_text_custom_emoji', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('document_id')->nullable();
            $table->index('document_id', 'ix_9878430b44e19b6df13996c7');
            $table->text('alt')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_32433fd7aee1b22943f5f2cf');
            $table->index('account_id', 'ix_7f06e370f91ee8c63abbee4f');
        });
        Schema::create('tl_rich_text_text_date', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('relative')->default(false);
            $table->boolean('short_time')->default(false);
            $table->boolean('long_time')->default(false);
            $table->boolean('short_date')->default(false);
            $table->boolean('long_date')->default(false);
            $table->boolean('day_of_week')->default(false);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_8d6532d442c8d76575845dc7');
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c09668b7e1066c72ca72ff3e');
            $table->index('account_id', 'ix_f58595960cdc440e6d40ef1b');
        });
        Schema::create('tl_rich_text_text_email', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_7d7f8366e094f71a7f2d136f');
            $table->text('email')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c38467fdcb276ef1dff806fb');
            $table->index('account_id', 'ix_21a4498f45cc9b4901e3ac1f');
        });
        Schema::create('tl_rich_text_text_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5b39d0feaf68ce53d45871db');
            $table->index('account_id', 'ix_7694e3beadfa66d4bba22905');
        });
        Schema::create('tl_rich_text_text_fixed', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_97393df395657100a55bd25f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4b2db88038b1b4706f25608d');
            $table->index('account_id', 'ix_10f8fdf1ae4afe432789c7d3');
        });
        Schema::create('tl_rich_text_text_hashtag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_524aedd2f8f60df24145bbb3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2760b65938ec575c6d0761d5');
            $table->index('account_id', 'ix_489c35a3cf7fb7c5679d827f');
        });
        Schema::create('tl_rich_text_text_image', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('document_id')->nullable();
            $table->index('document_id', 'ix_68bae00b459aa6d14e4d3961');
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5b7fe1f11c938b974524b5fb');
            $table->index('account_id', 'ix_1c4df92f43e06fe1455ece7e');
        });
        Schema::create('tl_rich_text_text_italic', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_b3b0cfb9b97fb644d3c0a8f9');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_535b6bdfe1522372525df8b1');
            $table->index('account_id', 'ix_3a08717611ada8f599627ea4');
        });
        Schema::create('tl_rich_text_text_marked', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_97adb9eb63b1f181d4bd2dc8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ea4ab7fce23ec82bbb180cc2');
            $table->index('account_id', 'ix_2e0788385dee30ee7f6359d3');
        });
        Schema::create('tl_rich_text_text_math', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('source')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_128f097215358cfb515be5ee');
            $table->index('account_id', 'ix_939eb00e3a69b4384501cdcd');
        });
        Schema::create('tl_rich_text_text_mention', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_4afccf9c9528edcea79f8f21');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c00bc388beb1634907d1c215');
            $table->index('account_id', 'ix_c8a1903c69dfb1f0c9a818d0');
        });
        Schema::create('tl_rich_text_text_mention_name', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_84b0f3096fe2d9bae416661e');
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_25ea498675a1427a77a62aed');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7da223ef48bd15bbdf6f92c3');
            $table->index('account_id', 'ix_fd1a65160793e4183a79c878');
        });
        Schema::create('tl_rich_text_text_phone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_5d5529aa37bc405bce3e0682');
            $table->text('phone')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_49c48fb085806456882e075d');
            $table->index('account_id', 'ix_21cb39d69bbdb07c14c17f8b');
        });
        Schema::create('tl_rich_text_text_plain', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2e68ae2207a617689af88d29');
            $table->index('account_id', 'ix_05cd843194598c04e491e914');
        });
        Schema::create('tl_rich_text_text_spoiler', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_83ead6f0faaae2f75bcb2c3f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8e84551d1281c0526297722e');
            $table->index('account_id', 'ix_d5bc8d70a5596faf619b643a');
        });
        Schema::create('tl_rich_text_text_strike', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_b9de23300170f3f1db7ace1c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b200383705ca8fce2ef60e19');
            $table->index('account_id', 'ix_82d22547d81d22f8aeb2ca21');
        });
        Schema::create('tl_rich_text_text_subscript', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_47f6d3c3ad2491a54222b97a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f6d2eab3442b75a569126e23');
            $table->index('account_id', 'ix_073cae9a8687fec6579555b2');
        });
        Schema::create('tl_rich_text_text_superscript', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_4b816c7487c11e5f4938dd43');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b84807ea6588a4755d862514');
            $table->index('account_id', 'ix_6c0c8605c2b26dd4b3c4e200');
        });
        Schema::create('tl_rich_text_text_underline', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_139af1c7848bb46af5bc40bb');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b67594a3498f1a35740206bd');
            $table->index('account_id', 'ix_52c99b464b345601648eb7dc');
        });
        Schema::create('tl_rich_text_text_url', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_c984b872e0039664a18483ee');
            $table->text('url')->nullable();
            $table->bigInteger('webpage_id')->nullable();
            $table->index('webpage_id', 'ix_41d0d38d2360d53456c79b1e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c89b89d0eb7bbb53dd86fbc7');
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
    }
};
