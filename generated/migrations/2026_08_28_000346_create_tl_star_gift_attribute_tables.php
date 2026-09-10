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
        Schema::create('tl_star_gift_attribute_star_gift_attribute_backdrop', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('name')->nullable();
            $table->integer('backdrop_id')->nullable();
            $table->integer('center_color')->nullable();
            $table->integer('edge_color')->nullable();
            $table->integer('pattern_color')->nullable();
            $table->integer('text_color')->nullable();
            $table->bigInteger('rarity')->nullable();
            $table->index('rarity', 'ix_a51e455b0b451cd92d00cc3e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ed6525ca4fe7ccc43498a775');
            $table->index('account_id', 'ix_81adb27e7754ded46382e0ed');
        });
        Schema::create('tl_star_gift_attribute_star_gift_attribute_model', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('crafted')->default(false);
            $table->text('name')->nullable();
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_ef5d14e2f710d2c145ab9c15');
            $table->bigInteger('rarity')->nullable();
            $table->index('rarity', 'ix_450c44aaea7e37a5c6176b09');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_00566254b741907d230b4f7f');
            $table->index('account_id', 'ix_08584df5de456af2994a5337');
        });
        Schema::create('tl_star_gift_attribute_star_gift_attribute_or_06bf30da1b7d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('sender_id')->nullable();
            $table->index('sender_id', 'ix_ed3c8c59c81823670e8e7b8e');
            $table->bigInteger('recipient_id')->nullable();
            $table->index('recipient_id', 'ix_7e7cf69294abab1452cc6070');
            $table->integer('date')->nullable();
            $table->bigInteger('message')->nullable();
            $table->index('message', 'ix_fb8f82d30f943b09deca25b8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8cf92ad3c2e53c98e8b80574');
            $table->index('account_id', 'ix_d1126fd4765a2321acdd392a');
        });
        Schema::create('tl_star_gift_attribute_star_gift_attribute_pattern', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('name')->nullable();
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_4beb34b5ce8d2a344da3dac8');
            $table->bigInteger('rarity')->nullable();
            $table->index('rarity', 'ix_140fb650fa93884039472987');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c589557200a1a85846409cdb');
            $table->index('account_id', 'ix_00d4a58686c1a118f5348613');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_attribute_star_gift_attribute_pattern');
        Schema::dropIfExists('tl_star_gift_attribute_star_gift_attribute_or_06bf30da1b7d');
        Schema::dropIfExists('tl_star_gift_attribute_star_gift_attribute_model');
        Schema::dropIfExists('tl_star_gift_attribute_star_gift_attribute_backdrop');
    }
};
