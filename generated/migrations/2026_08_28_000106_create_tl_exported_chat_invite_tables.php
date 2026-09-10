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
        Schema::create('tl_exported_chat_invite_chat_invite_exported', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('revoked')->default(false);
            $table->boolean('permanent')->default(false);
            $table->boolean('request_needed')->default(false);
            $table->text('link')->nullable();
            $table->bigInteger('admin_id')->nullable();
            $table->index('admin_id', 'ix_707f6a65eb3c0e02907e7d7f');
            $table->integer('date')->nullable();
            $table->integer('start_date')->nullable();
            $table->integer('expire_date')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage')->nullable();
            $table->integer('requested')->nullable();
            $table->integer('subscription_expired')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('subscription_pricing')->nullable();
            $table->index('subscription_pricing', 'ix_7706b5bfee2a79cbd3cf2923');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_786dda3593b72c16fc365cc0');
            $table->index('account_id', 'ix_8961b22a55e790d8d8f2c3c9');
        });
        Schema::create('tl_exported_chat_invite_chat_invite_public_join_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_25fcf078b8c49c7585a398b6');
            $table->index('account_id', 'ix_17a13442818c6d80a740466a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_exported_chat_invite_chat_invite_public_join_requests');
        Schema::dropIfExists('tl_exported_chat_invite_chat_invite_exported');
    }
};
