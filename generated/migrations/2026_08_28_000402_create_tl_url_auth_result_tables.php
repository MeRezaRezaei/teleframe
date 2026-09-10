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
        Schema::create('tl_url_auth_result_url_auth_result_accepted', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e92a51c50bbdbfe335843af9');
            $table->index('account_id', 'ix_8127c8686f7cf99cbd7676eb');
        });
        Schema::create('tl_url_auth_result_url_auth_result_default', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4793bf78c64596c000a47def');
            $table->index('account_id', 'ix_2876016038f4dd39de03de10');
        });
        Schema::create('tl_url_auth_result_url_auth_result_request', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('request_write_access')->default(false);
            $table->boolean('request_phone_number')->default(false);
            $table->boolean('match_codes_first')->default(false);
            $table->boolean('is_app')->default(false);
            $table->bigInteger('bot')->nullable();
            $table->index('bot', 'ix_a8cc1fee0c18561adc123b02');
            $table->text('domain')->nullable();
            $table->text('browser')->nullable();
            $table->text('platform')->nullable();
            $table->text('ip')->nullable();
            $table->text('region')->nullable();
            $table->bigInteger('user_id_hint')->nullable();
            $table->text('verified_app_name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4e9369dd3573ed1be5796dec');
            $table->index('account_id', 'ix_c17e7c63a83b1639a8de4510');
        });
        Schema::create('tl_url_auth_result_url_auth_result_request__match_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_bac33b8e64c9e15801a9b8d1')->references('id')->on('tl_url_auth_result_url_auth_result_request')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a68140065f6352cfe79e');
            $table->index('account_id', 'ix_c204e80bc348ff11eb880da2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_url_auth_result_url_auth_result_request__match_codes');
        Schema::dropIfExists('tl_url_auth_result_url_auth_result_request');
        Schema::dropIfExists('tl_url_auth_result_url_auth_result_default');
        Schema::dropIfExists('tl_url_auth_result_url_auth_result_accepted');
    }
};
