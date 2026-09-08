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
        Schema::create('tl_channel_admin_log_event_action', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_a48f273010c3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->text('prev_value');
            $table->text('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_73ca016088c9', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_value');
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_80c49994fae5', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_value');
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_2ab9a74c160a', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_stickerset');
            $table->uuid('new_stickerset');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_b62cde8f67b1', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->integer('prev_value');
            $table->integer('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_30be48f8e9ac', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->bigInteger('prev_value');
            $table->bigInteger('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_6b0307fdb532', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_value');
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_c471b00034b6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_value');
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_1d1939e936ae', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_photo');
            $table->uuid('new_photo');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_ec341efadcce', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_value');
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_ec676b493c8e', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_stickerset');
            $table->uuid('new_stickerset');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_7b0e57ba2a4d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->text('prev_value');
            $table->text('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_fed8d2f79997', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->text('prev_value');
            $table->text('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_38f150219e2e', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_17108c5055e7', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channel_admin_log_event_action_channel_adm_38f150219e2e')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_a839b7545e1e0b4f9a5e');
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_54649cabd20d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channel_admin_log_event_action_channel_adm_38f150219e2e')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_93fc98dc60b9a5bd8c40');
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_573029f966f9', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_value');
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_b47bb8864476', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('topic');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_d30d8ce61800', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_banned_rights');
            $table->uuid('new_banned_rights');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_68e2b9c5e1b0', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('message');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_d3cf76f819fa', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('topic');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_2b0f43eb9ce7', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('call');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_c24d817da4e3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_message');
            $table->uuid('new_message');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_1c072a4a5876', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_topic');
            $table->uuid('new_topic');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_9a2b51c0be87', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('invite');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_88f46dc9c3bc', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_invite');
            $table->uuid('new_invite');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_c2c6ac3df795', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('invite');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_8c27ac0ad9ec', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->text('prev_rank');
            $table->text('new_rank');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_6750f366ea9f', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('participant');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_def61dc1b5d2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_d1316284f5f1', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('via_chatlist')->default(false);
            $table->uuid('invite');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_80099d468567', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('invite');
            $table->bigInteger('approved_by');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_de4a28a2dca7', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_5db283c442db', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('participant');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_bee777613e0b', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_participant');
            $table->uuid('new_participant');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_91ce010fdbd1', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_participant');
            $table->uuid('new_participant');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_b8859bb8b9a0', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('prev_participant');
            $table->uuid('new_participant');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_f449405b5cbf', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('participant');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_95eba04565f5', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('participant');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_cc538425dc48', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('prev_topic')->nullable();
            $table->uuid('new_topic')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_f4fc4f0e630e', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('message');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_2277da9e9c20', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('call');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_af87ed20f3e9', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('message');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_ce8accd51648', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_a2bc71fad8b3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_f7a3eea55c58', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_d6846eefc0e8', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('join_muted');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_7f823e6a896e', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_e9b139f92b2c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_feec52c78ffd', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_528eef173acf', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_1280d69ef5fa', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_5b0d4c7f48ce', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->integer('prev_value');
            $table->integer('new_value');
            $table->timestamps();
        });
        Schema::create('tl_channel_admin_log_event_action_channel_adm_53060ff0c76a', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_event_action')->cascadeOnDelete();
            $table->uuid('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_53060ff0c76a');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_5b0d4c7f48ce');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_1280d69ef5fa');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_528eef173acf');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_feec52c78ffd');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_e9b139f92b2c');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_7f823e6a896e');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_d6846eefc0e8');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_f7a3eea55c58');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_a2bc71fad8b3');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_ce8accd51648');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_af87ed20f3e9');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_2277da9e9c20');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_f4fc4f0e630e');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_cc538425dc48');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_95eba04565f5');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_f449405b5cbf');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_b8859bb8b9a0');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_91ce010fdbd1');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_bee777613e0b');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_5db283c442db');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_de4a28a2dca7');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_80099d468567');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_d1316284f5f1');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_def61dc1b5d2');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_6750f366ea9f');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_8c27ac0ad9ec');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_c2c6ac3df795');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_88f46dc9c3bc');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_9a2b51c0be87');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_1c072a4a5876');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_c24d817da4e3');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_2b0f43eb9ce7');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_d3cf76f819fa');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_68e2b9c5e1b0');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_d30d8ce61800');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_b47bb8864476');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_573029f966f9');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_54649cabd20d');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_17108c5055e7');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_38f150219e2e');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_fed8d2f79997');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_7b0e57ba2a4d');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_ec676b493c8e');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_ec341efadcce');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_1d1939e936ae');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_c471b00034b6');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_6b0307fdb532');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_30be48f8e9ac');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_b62cde8f67b1');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_2ab9a74c160a');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_80c49994fae5');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_73ca016088c9');
        Schema::dropIfExists('tl_channel_admin_log_event_action_channel_adm_a48f273010c3');
        Schema::dropIfExists('tl_channel_admin_log_event_action');
    }
};
