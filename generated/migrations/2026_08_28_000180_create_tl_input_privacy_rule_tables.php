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
        Schema::create('tl_input_privacy_rule_input_privacy_value_allow_all', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_71290b0936333ad55daa1f66');
            $table->index('account_id', 'ix_81417d643176540fb54cae0a');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_allow_bots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_49c627fcbf9eba9e32c1ada1');
            $table->index('account_id', 'ix_b41b8f67e4f31951c704a065');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_all_b839cc5564e5', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7fcdc2bad15c7306b31ad2a5');
            $table->index('account_id', 'ix_f2272e2255679b942708618f');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_all_c89442bd25b2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_input_privacy_rule_input_privacy_value_all_b839cc5564e5', 'id', 'fk_d7d34de11d7b1ad356b8b85c')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d9ead65f48235e2c0871');
            $table->index('account_id', 'ix_2fb82ff9cf78426856e0fede');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_all_4e73532530d9', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a42e32592649df205b65b8b2');
            $table->index('account_id', 'ix_edbda3a1f409e30847397afe');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_allow_contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_767753859cd95799df7cf62e');
            $table->index('account_id', 'ix_7aab929567a37d7a854c8058');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_allow_premium', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1838c474c9b7172bf3a42b0c');
            $table->index('account_id', 'ix_f0967b5c54d597034856f9c0');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_allow_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_29f9fa91a64c204f3ae6fa71');
            $table->index('account_id', 'ix_1d0b907b50596fed880173d1');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_all_71681af9150e', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_input_privacy_rule_input_privacy_value_allow_users', 'id', 'fk_d5b543482ce826537e3813ff')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6d73582cc6fa315e6f3a');
            $table->index('account_id', 'ix_a3d658b994b79636d1bab889');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_disallow_all', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1baa6ae1d386aafa45e161e5');
            $table->index('account_id', 'ix_4e14cd207390ca63fbfd568a');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_disallow_bots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_95f27bac70702ff2771ddc70');
            $table->index('account_id', 'ix_2596c69e2ff7a9d7c497e709');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_dis_92dd14476e43', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9eb53f0ab6435e5c5aabd4fa');
            $table->index('account_id', 'ix_0459af583b2774e53a2f0c2a');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_dis_bb41d2b871d8', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_input_privacy_rule_input_privacy_value_dis_92dd14476e43', 'id', 'fk_6c689e635c7186b5aa268c26')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_dcc5fa33f524e940c213');
            $table->index('account_id', 'ix_3d221cdac51319a0623f0954');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_dis_8a366d0a4f14', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c5886e3954cfbd8fe2c1c536');
            $table->index('account_id', 'ix_593ce19c83612ddb85daf34a');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_disallow_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bb3a1a0ecba7bb86781195af');
            $table->index('account_id', 'ix_64232e00830262cc6219e7f5');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_dis_d8b619151246', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_input_privacy_rule_input_privacy_value_disallow_users', 'id', 'fk_e72414b5916651446e83408a')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_047e252645687cc19c0e');
            $table->index('account_id', 'ix_356b4a28243abce9ab64417e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_dis_d8b619151246');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_disallow_users');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_dis_8a366d0a4f14');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_dis_bb41d2b871d8');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_dis_92dd14476e43');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_disallow_bots');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_disallow_all');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_all_71681af9150e');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_allow_users');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_allow_premium');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_allow_contacts');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_all_4e73532530d9');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_all_c89442bd25b2');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_all_b839cc5564e5');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_allow_bots');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_allow_all');
    }
};
