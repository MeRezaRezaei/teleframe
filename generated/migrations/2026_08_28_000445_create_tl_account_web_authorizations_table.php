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
        Schema::create('tl_account_web_authorizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8269e64c4763a89b33112877');
            $table->index('account_id', 'ix_6d333aac6cebf3345b1a6f4e');
        });
        Schema::create('tl_account_web_authorizations_web_authorizations', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_web_authorizations')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ad83764b78a44324862aa2a0');
        });
        Schema::create('tl_account_web_authorizations_web_authorizati_1c5ab83167e4', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_web_authorizations_web_authorizations')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8bbd97bf13fd13b3f083');
            $table->index('account_id', 'ix_626f068d8c1eea3600b5e614');
        });
        Schema::create('tl_account_web_authorizations_web_authorizations__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_web_authorizations_web_authorizations')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_account_web_authorizations');
    }
};
