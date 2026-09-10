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
        Schema::create('tl_page_block_input_page_block_map', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('geo')->nullable();
            $table->index('geo', 'ix_57d998be549e582c7f5a7011');
            $table->integer('zoom')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->bigInteger('caption')->nullable();
            $table->index('caption', 'ix_06885f408ec2fc54f236d7fc');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ef40a11f88b1ea022d15b851');
            $table->index('account_id', 'ix_31ac0e80e90a2bf62f5d4abd');
        });
        Schema::create('tl_page_block_page_block_anchor', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5fb6a0dbf8fb063b2d9fc64f');
            $table->index('account_id', 'ix_0c38f2fab21445c557e1d929');
        });
        Schema::create('tl_page_block_page_block_audio', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('audio_id')->nullable();
            $table->index('audio_id', 'ix_e3937e9bf3e01d52f3d13a11');
            $table->bigInteger('caption')->nullable();
            $table->index('caption', 'ix_d22023fc30833a72b76c5018');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a61a09cc84fb03ab546bc688');
            $table->index('account_id', 'ix_46643a751041c07f4ae90018');
        });
        Schema::create('tl_page_block_page_block_author_date', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('author')->nullable();
            $table->index('author', 'ix_fabc046d0b2541b3c7c86540');
            $table->integer('published_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1faa956cff8f18c7d24f9f5d');
            $table->index('account_id', 'ix_2c61827c171c34858c6e0b8f');
        });
        Schema::create('tl_page_block_page_block_blockquote', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_3311aa799ca4516dface201c');
            $table->bigInteger('caption')->nullable();
            $table->index('caption', 'ix_01d554972f767000ec768f4a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c4a155157ecd55b2cc7f5a6b');
            $table->index('account_id', 'ix_d718d25fca41ff63c3844d06');
        });
        Schema::create('tl_page_block_page_block_blockquote_blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('caption')->nullable();
            $table->index('caption', 'ix_c9b7d2beb0e0a6324454ae43');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_72bad08ee53e6e4a075e6670');
            $table->index('account_id', 'ix_3183b2ea96ca5f64fc7f81d3');
        });
        Schema::create('tl_page_block_page_block_blockquote_blocks__blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_block_page_block_blockquote_blocks', 'id', 'fk_63db4303d021c32fa56e93ef')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7b3df98327b38690e4a6');
            $table->index('account_id', 'ix_35bfb623700dd156a2cd31dc');
        });
        Schema::create('tl_page_block_page_block_channel', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('channel')->nullable();
            $table->index('channel', 'ix_a30b157bac375faf2598d4e1');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3e2dac7d6345adfc06812221');
            $table->index('account_id', 'ix_fc96c0424bde76d52fce3fa8');
        });
        Schema::create('tl_page_block_page_block_collage', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('caption')->nullable();
            $table->index('caption', 'ix_075b74081c051bc7b5adceae');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0517b1753f2484ff602e2207');
            $table->index('account_id', 'ix_6347ff4a961e71b5f1764848');
        });
        Schema::create('tl_page_block_page_block_collage__items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_block_page_block_collage', 'id', 'fk_43f8c23d381fac67724d5c60')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_720d481cea6b4ab71fe7');
            $table->index('account_id', 'ix_4c62e3230bbb74c9530c4526');
        });
        Schema::create('tl_page_block_page_block_cover', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('cover')->nullable();
            $table->index('cover', 'ix_916768299d3257eef753b8ec');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f7d41e3cb0d15b1066ed3085');
            $table->index('account_id', 'ix_5a10a83fbacb7d5ad04789f0');
        });
        Schema::create('tl_page_block_page_block_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('open')->default(false);
            $table->bigInteger('title')->nullable();
            $table->index('title', 'ix_425a372f077cff54663db504');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_285f313e2b672bdff4272f83');
            $table->index('account_id', 'ix_7fff52cdc654e1fdee53e8be');
        });
        Schema::create('tl_page_block_page_block_details__blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_block_page_block_details', 'id', 'fk_b42759df86a2c3a1d71cfb37')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_859912cae7c504280b28');
            $table->index('account_id', 'ix_4448e931ef9a7cb2426011ae');
        });
        Schema::create('tl_page_block_page_block_divider', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e7c08634a4e74d4db4c5ebd1');
            $table->index('account_id', 'ix_9a5a491de9ed828f16fe6c78');
        });
        Schema::create('tl_page_block_page_block_embed', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('full_width')->default(false);
            $table->boolean('allow_scrolling')->default(false);
            $table->text('url')->nullable();
            $table->text('html')->nullable();
            $table->bigInteger('poster_photo_id')->nullable();
            $table->index('poster_photo_id', 'ix_60ac4b40e8721c41c4c2c939');
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->bigInteger('caption')->nullable();
            $table->index('caption', 'ix_ad30c766df88d5e408513897');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c7e60df7af180c78ec08394c');
            $table->index('account_id', 'ix_b6e8961ae17b5daad0c0cb6d');
        });
        Schema::create('tl_page_block_page_block_embed_post', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('webpage_id')->nullable();
            $table->index('webpage_id', 'ix_e224ab7a6880798dc9081b15');
            $table->bigInteger('author_photo_id')->nullable();
            $table->index('author_photo_id', 'ix_307cff5be83518c57ea33dfc');
            $table->text('author')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('caption')->nullable();
            $table->index('caption', 'ix_7e1f7141349a4f8ef83ae2bd');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_631b1275355775cb9db58949');
            $table->index('account_id', 'ix_c4450ba42fa96b1a34470b7f');
        });
        Schema::create('tl_page_block_page_block_embed_post__blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_block_page_block_embed_post', 'id', 'fk_4c9113ab215c42acad28c275')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5f6d0b58edd9f985366e');
            $table->index('account_id', 'ix_f5fefdcbd73ff7ad831eb375');
        });
        Schema::create('tl_page_block_page_block_footer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_e3383922af9feb006341911a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3f1eba55385bd01d33c59068');
            $table->index('account_id', 'ix_84accd093c8f917ff7dea76e');
        });
        Schema::create('tl_page_block_page_block_header', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_5533985ffa5e85c7e7b08ab7');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8a79b4f51af0e60b3520e1d1');
            $table->index('account_id', 'ix_dfd03d449e86a232902c44a2');
        });
        Schema::create('tl_page_block_page_block_heading1', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_24ccc6a6180b8b7dc4f4b591');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9ef81ac5a51aab9e13dab3a9');
            $table->index('account_id', 'ix_13682e88597c090236993549');
        });
        Schema::create('tl_page_block_page_block_heading2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_b32e826a6b8346fb5599666a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_db93748458c43f4966a3beb9');
            $table->index('account_id', 'ix_c3c25151498876399c212f30');
        });
        Schema::create('tl_page_block_page_block_heading3', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_ff2534d9c3039827f6eaa62d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8ce78541b4bf4bf2d41c0e32');
            $table->index('account_id', 'ix_2d8b8d2225e39633e52d270e');
        });
        Schema::create('tl_page_block_page_block_heading4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_d9a4b311a4bf6505e8738fac');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_30174d1d251f587fb0569d3f');
            $table->index('account_id', 'ix_d41dfdf8a8c03c9be64ab1fb');
        });
        Schema::create('tl_page_block_page_block_heading5', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_992ac53baa67c27e6b002ccd');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8549bd96382454d2cda601c5');
            $table->index('account_id', 'ix_d9a1b55f7b1467ff75412509');
        });
        Schema::create('tl_page_block_page_block_heading6', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_8c2f1d068ddeb62e92ea25fb');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7625e16fb991fe25f0ab7e26');
            $table->index('account_id', 'ix_25036f4bb4bd11e1187338ef');
        });
        Schema::create('tl_page_block_page_block_kicker', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_3d344ae37c4820ae60e5e9cb');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1f92ee59c146c5be87b3c814');
            $table->index('account_id', 'ix_554a22c72c378af59a2534c7');
        });
        Schema::create('tl_page_block_page_block_list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_906b8bdb311ff5f1c37e7eec');
            $table->index('account_id', 'ix_32ac7132d659d754ba192ad3');
        });
        Schema::create('tl_page_block_page_block_list__items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_block_page_block_list', 'id', 'fk_416b66c97fe5ccb5b43762b7')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c20092672864acd6d10d');
            $table->index('account_id', 'ix_a3e9f5eb4e16110dc525e30a');
        });
        Schema::create('tl_page_block_page_block_map', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('geo')->nullable();
            $table->index('geo', 'ix_09f1325eeaca3dc2a7da8440');
            $table->integer('zoom')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->bigInteger('caption')->nullable();
            $table->index('caption', 'ix_4ae4e63335998f375067030c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8c2abca53e16696f6873e9e5');
            $table->index('account_id', 'ix_6461206cdd136c927b11654c');
        });
        Schema::create('tl_page_block_page_block_math', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('source')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5db13f3ff5c9f08da9788579');
            $table->index('account_id', 'ix_1b3d5c1dac45e4fccdc85ea3');
        });
        Schema::create('tl_page_block_page_block_ordered_list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('reversed')->default(false);
            $table->integer('start')->nullable();
            $table->text('tl_type')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_71e9dc4ed1f30e4d15244b2e');
            $table->index('account_id', 'ix_821674086b4d924fc7838f93');
        });
        Schema::create('tl_page_block_page_block_ordered_list__items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_block_page_block_ordered_list', 'id', 'fk_3d26448ed5f16c7b8fdb31f8')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1f9b94bb9519b84a2f23');
            $table->index('account_id', 'ix_cad52523e63b49fec5cb9f41');
        });
        Schema::create('tl_page_block_page_block_paragraph', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_03f0e6f207f54cee93fce54e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f2b2c797d9c1fc93e12410ce');
            $table->index('account_id', 'ix_6c3f22cf4f416c2cad323ec5');
        });
        Schema::create('tl_page_block_page_block_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->bigInteger('photo_id')->nullable();
            $table->index('photo_id', 'ix_4b27efc9c9a6bdf4eb02c907');
            $table->bigInteger('caption')->nullable();
            $table->index('caption', 'ix_af03589c2dd4cd83d119003c');
            $table->text('url')->nullable();
            $table->bigInteger('webpage_id')->nullable();
            $table->index('webpage_id', 'ix_99d1dae35fc65bbb8d015a5d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_549231054b17393c6d5f6cb6');
            $table->index('account_id', 'ix_35f9b782f870cb29be5e811f');
        });
        Schema::create('tl_page_block_page_block_preformatted', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_407ac4122d8bbbb2fe038978');
            $table->text('language')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7a22fb1f631485aa5199906d');
            $table->index('account_id', 'ix_f03772dbe71dca6b99e591fe');
        });
        Schema::create('tl_page_block_page_block_pullquote', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_8fcb7a70ec060e500f250283');
            $table->bigInteger('caption')->nullable();
            $table->index('caption', 'ix_ea35fc0b84261b90d36feab6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d21a260275b8862612f4f717');
            $table->index('account_id', 'ix_c030c13c8f8dacc9483e9dd2');
        });
        Schema::create('tl_page_block_page_block_related_articles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('title')->nullable();
            $table->index('title', 'ix_a9099c65fdcac68450f7d051');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ff4f784743fcefd2ab5d4afe');
            $table->index('account_id', 'ix_3796049c50647c2d0e49bb58');
        });
        Schema::create('tl_page_block_page_block_related_articles__articles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_block_page_block_related_articles', 'id', 'fk_f774068623669c014a3beef6')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_be2bc84781faf68e73f2');
            $table->index('account_id', 'ix_cf6da140c484701b29524810');
        });
        Schema::create('tl_page_block_page_block_slideshow', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('caption')->nullable();
            $table->index('caption', 'ix_8596dbb1c01461215ffd92c9');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c198f7443bc389923be593c9');
            $table->index('account_id', 'ix_a99760360ca577715b93e3a0');
        });
        Schema::create('tl_page_block_page_block_slideshow__items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_block_page_block_slideshow', 'id', 'fk_69260a249dfd567da51c934b')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6a89d8998a71271ed527');
            $table->index('account_id', 'ix_a89aecb896bc608a6dc2385c');
        });
        Schema::create('tl_page_block_page_block_subheader', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_5ca6651f7b6cd660763791dc');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4c786ea496ad33069def057d');
            $table->index('account_id', 'ix_0db1770840669bbd23e31439');
        });
        Schema::create('tl_page_block_page_block_subtitle', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_0d6677ce0317c9c204df4899');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_831a58a9f9993c844c64f274');
            $table->index('account_id', 'ix_4494809a9c04e69ba355588d');
        });
        Schema::create('tl_page_block_page_block_table', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('bordered')->default(false);
            $table->boolean('striped')->default(false);
            $table->bigInteger('title')->nullable();
            $table->index('title', 'ix_cfc1552933d0ef61f64799dd');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3b10b196d75e7a15468f89de');
            $table->index('account_id', 'ix_4c2cbc655b1f86904eda3854');
        });
        Schema::create('tl_page_block_page_block_table__rows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_page_block_page_block_table', 'id', 'fk_9b236b6cb3b74c8112e3ce3b')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_eb5f586967035f590de3');
            $table->index('account_id', 'ix_be635b9e81bd5c188d4db752');
        });
        Schema::create('tl_page_block_page_block_thinking', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_e7d533a1ff23fdfb43bc96d7');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_743b13cc147c3e02a4e57964');
            $table->index('account_id', 'ix_020ef2ff4a88d641acb57d4e');
        });
        Schema::create('tl_page_block_page_block_title', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_a2f546f0bbc59b94fe1851d7');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4f761cd4c6632380018c53c2');
            $table->index('account_id', 'ix_e12b050b23550977eafd6fd8');
        });
        Schema::create('tl_page_block_page_block_unsupported', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c977a50a7fadf4d5d7752cff');
            $table->index('account_id', 'ix_1157062e922da9e85fdd9e2a');
        });
        Schema::create('tl_page_block_page_block_video', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('autoplay')->default(false);
            $table->boolean('loop')->default(false);
            $table->boolean('spoiler')->default(false);
            $table->bigInteger('video_id')->nullable();
            $table->index('video_id', 'ix_ce8da0f6c7ee08567ea03fc7');
            $table->bigInteger('caption')->nullable();
            $table->index('caption', 'ix_83023e2aec90ddf914735af5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fe8bb42972aa05f9a95df5a5');
            $table->index('account_id', 'ix_2b9e75df8dfc997705feabd0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_block_page_block_video');
        Schema::dropIfExists('tl_page_block_page_block_unsupported');
        Schema::dropIfExists('tl_page_block_page_block_title');
        Schema::dropIfExists('tl_page_block_page_block_thinking');
        Schema::dropIfExists('tl_page_block_page_block_table__rows');
        Schema::dropIfExists('tl_page_block_page_block_table');
        Schema::dropIfExists('tl_page_block_page_block_subtitle');
        Schema::dropIfExists('tl_page_block_page_block_subheader');
        Schema::dropIfExists('tl_page_block_page_block_slideshow__items');
        Schema::dropIfExists('tl_page_block_page_block_slideshow');
        Schema::dropIfExists('tl_page_block_page_block_related_articles__articles');
        Schema::dropIfExists('tl_page_block_page_block_related_articles');
        Schema::dropIfExists('tl_page_block_page_block_pullquote');
        Schema::dropIfExists('tl_page_block_page_block_preformatted');
        Schema::dropIfExists('tl_page_block_page_block_photo');
        Schema::dropIfExists('tl_page_block_page_block_paragraph');
        Schema::dropIfExists('tl_page_block_page_block_ordered_list__items');
        Schema::dropIfExists('tl_page_block_page_block_ordered_list');
        Schema::dropIfExists('tl_page_block_page_block_math');
        Schema::dropIfExists('tl_page_block_page_block_map');
        Schema::dropIfExists('tl_page_block_page_block_list__items');
        Schema::dropIfExists('tl_page_block_page_block_list');
        Schema::dropIfExists('tl_page_block_page_block_kicker');
        Schema::dropIfExists('tl_page_block_page_block_heading6');
        Schema::dropIfExists('tl_page_block_page_block_heading5');
        Schema::dropIfExists('tl_page_block_page_block_heading4');
        Schema::dropIfExists('tl_page_block_page_block_heading3');
        Schema::dropIfExists('tl_page_block_page_block_heading2');
        Schema::dropIfExists('tl_page_block_page_block_heading1');
        Schema::dropIfExists('tl_page_block_page_block_header');
        Schema::dropIfExists('tl_page_block_page_block_footer');
        Schema::dropIfExists('tl_page_block_page_block_embed_post__blocks');
        Schema::dropIfExists('tl_page_block_page_block_embed_post');
        Schema::dropIfExists('tl_page_block_page_block_embed');
        Schema::dropIfExists('tl_page_block_page_block_divider');
        Schema::dropIfExists('tl_page_block_page_block_details__blocks');
        Schema::dropIfExists('tl_page_block_page_block_details');
        Schema::dropIfExists('tl_page_block_page_block_cover');
        Schema::dropIfExists('tl_page_block_page_block_collage__items');
        Schema::dropIfExists('tl_page_block_page_block_collage');
        Schema::dropIfExists('tl_page_block_page_block_channel');
        Schema::dropIfExists('tl_page_block_page_block_blockquote_blocks__blocks');
        Schema::dropIfExists('tl_page_block_page_block_blockquote_blocks');
        Schema::dropIfExists('tl_page_block_page_block_blockquote');
        Schema::dropIfExists('tl_page_block_page_block_author_date');
        Schema::dropIfExists('tl_page_block_page_block_audio');
        Schema::dropIfExists('tl_page_block_page_block_anchor');
        Schema::dropIfExists('tl_page_block_input_page_block_map');
    }
};
