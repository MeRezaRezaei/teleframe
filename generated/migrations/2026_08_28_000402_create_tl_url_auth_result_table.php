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
        Schema::create('tl_url_auth_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_d752756bdcde6febd873ce6a');
            $table->index('account_id', 'ix_27f2fed33e2fda8e5bdb5506');
        });
        Schema::create('tl_url_auth_result_url_auth_result_accepted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_url_auth_result')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8127c8686f7cf99cbd7676eb');
        });
        Schema::create('tl_url_auth_result_url_auth_result_default', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_url_auth_result')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2876016038f4dd39de03de10');
        });
        Schema::create('tl_url_auth_result_url_auth_result_request', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_url_auth_result')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('request_write_access')->default(false);
            $table->boolean('request_phone_number')->default(false);
            $table->boolean('match_codes_first')->default(false);
            $table->boolean('is_app')->default(false);
            $table->uuid('bot');
            $table->index('bot', 'ix_a8cc1fee0c18561adc123b02');
            $table->text('domain');
            $table->text('browser')->nullable();
            $table->text('platform')->nullable();
            $table->text('ip')->nullable();
            $table->text('region')->nullable();
            $table->bigInteger('user_id_hint')->nullable();
            $table->text('verified_app_name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c17e7c63a83b1639a8de4510');
        });
        Schema::create('tl_url_auth_result_url_auth_result_request__match_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_url_auth_result_url_auth_result_request')->cascadeOnDelete();
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
        Schema::dropIfExists('tl_url_auth_result');
    }
};
