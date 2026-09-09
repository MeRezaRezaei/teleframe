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
        Schema::create('tl_request_peer_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1b578c7e735db8dc314fcffa');
            $table->index('account_id', 'ix_a1a6ce904e6297adbd2b6dd6');
        });
        Schema::create('tl_request_peer_type_request_peer_type_broadcast', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_request_peer_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->uuid('has_username')->nullable();
            $table->index('has_username', 'ix_5c6c36c7698c94bfcba62a27');
            $table->uuid('user_admin_rights')->nullable();
            $table->index('user_admin_rights', 'ix_22a90402571d026bb42f828c');
            $table->uuid('bot_admin_rights')->nullable();
            $table->index('bot_admin_rights', 'ix_6cd9fbc1e25cc388fb791dbc');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4452b7e510fd7888c682ba80');
        });
        Schema::create('tl_request_peer_type_request_peer_type_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_request_peer_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->boolean('bot_participant')->default(false);
            $table->uuid('has_username')->nullable();
            $table->index('has_username', 'ix_d42d073d1584a8d4a8720c61');
            $table->uuid('forum')->nullable();
            $table->index('forum', 'ix_40cecb44f791333785d2e832');
            $table->uuid('user_admin_rights')->nullable();
            $table->index('user_admin_rights', 'ix_45db71a722643f1dbf6ca24c');
            $table->uuid('bot_admin_rights')->nullable();
            $table->index('bot_admin_rights', 'ix_3505efde4a003b9014b2bc17');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_75e2a90b893b5e56b3d5cb27');
        });
        Schema::create('tl_request_peer_type_request_peer_type_create_bot', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_request_peer_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('bot_managed')->default(false);
            $table->text('suggested_name')->nullable();
            $table->text('suggested_username')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ea353bbbf2db69ec281ebb44');
        });
        Schema::create('tl_request_peer_type_request_peer_type_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_request_peer_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('bot')->nullable();
            $table->index('bot', 'ix_f1b51bc60cec0c5ed8e683c9');
            $table->uuid('premium')->nullable();
            $table->index('premium', 'ix_5c036658d1e5d46ec0bed9ee');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_47234b52871463fa48f5a399');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_request_peer_type_request_peer_type_user');
        Schema::dropIfExists('tl_request_peer_type_request_peer_type_create_bot');
        Schema::dropIfExists('tl_request_peer_type_request_peer_type_chat');
        Schema::dropIfExists('tl_request_peer_type_request_peer_type_broadcast');
        Schema::dropIfExists('tl_request_peer_type');
    }
};
