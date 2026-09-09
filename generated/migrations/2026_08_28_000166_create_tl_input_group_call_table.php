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
        Schema::create('tl_input_group_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_10ca1b7654cec254fc6d0fcc');
            $table->index('account_id', 'ix_7c3212fc494915ef0c490d34');
        });
        Schema::create('tl_input_group_call_input_group_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_group_call')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8d3755c2a2519b294ddc9b74');
            $table->unique(['account_id', 'tl_id'], 'ux_6b7a313c4dc1a5a995df');
        });
        Schema::create('tl_input_group_call_input_group_call_invite_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_group_call')->cascadeOnDelete();
            $table->integer('msg_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6e3d26e4d1bbdf7988a058ba');
        });
        Schema::create('tl_input_group_call_input_group_call_slug', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_group_call')->cascadeOnDelete();
            $table->text('slug');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c5c5959568796af1be2fb9dc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_group_call_input_group_call_slug');
        Schema::dropIfExists('tl_input_group_call_input_group_call_invite_message');
        Schema::dropIfExists('tl_input_group_call_input_group_call');
        Schema::dropIfExists('tl_input_group_call');
    }
};
