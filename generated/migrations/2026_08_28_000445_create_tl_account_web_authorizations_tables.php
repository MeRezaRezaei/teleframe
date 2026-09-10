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
        Schema::create('tl_account_web_authorizations_web_authorizations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_afa56059fc1030340832a2e9');
            $table->index('account_id', 'ix_ad83764b78a44324862aa2a0');
        });
        Schema::create('tl_account_web_authorizations_web_authorizati_1c5ab83167e4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_02f2f2d9aacbe2275ed64d99')->references('id')->on('tl_account_web_authorizations_web_authorizations')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8bbd97bf13fd13b3f083');
            $table->index('account_id', 'ix_626f068d8c1eea3600b5e614');
        });
        Schema::create('tl_account_web_authorizations_web_authorizations__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_61e93dc56dbe3e8c4520a163')->references('id')->on('tl_account_web_authorizations_web_authorizations')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ab86dad57a96ce33cd15');
            $table->index('account_id', 'ix_eb8d0f75e37f68dfee512eae');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_web_authorizations_web_authorizations__users');
        Schema::dropIfExists('tl_account_web_authorizations_web_authorizati_1c5ab83167e4');
        Schema::dropIfExists('tl_account_web_authorizations_web_authorizations');
    }
};
