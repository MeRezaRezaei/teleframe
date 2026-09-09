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
        Schema::create('tl_paid_reaction_privacy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e8c297b54ee1cb0608b91e62');
            $table->index('account_id', 'ix_76f3b3d6089acd39d8253523');
        });
        Schema::create('tl_paid_reaction_privacy_paid_reaction_privacy_anonymous', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_paid_reaction_privacy')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_94d2029fb27f7ce1c21a4933');
        });
        Schema::create('tl_paid_reaction_privacy_paid_reaction_privacy_default', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_paid_reaction_privacy')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1e90091e6fad85b0e6cfbb57');
        });
        Schema::create('tl_paid_reaction_privacy_paid_reaction_privacy_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_paid_reaction_privacy')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_533fc21cb928d2cc1ec030cf');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8fa92fc6fea1770cf0716e70');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_paid_reaction_privacy_paid_reaction_privacy_peer');
        Schema::dropIfExists('tl_paid_reaction_privacy_paid_reaction_privacy_default');
        Schema::dropIfExists('tl_paid_reaction_privacy_paid_reaction_privacy_anonymous');
        Schema::dropIfExists('tl_paid_reaction_privacy');
    }
};
