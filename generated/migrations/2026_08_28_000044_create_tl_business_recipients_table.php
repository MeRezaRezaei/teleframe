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
        Schema::create('tl_business_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_19762f4d1f3f8ecab0f98dae');
            $table->index('account_id', 'ix_a0d9278ddd339b4dd822472f');
        });
        Schema::create('tl_business_recipients_business_recipients', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_recipients')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('existing_chats')->default(false);
            $table->boolean('new_chats')->default(false);
            $table->boolean('contacts')->default(false);
            $table->boolean('non_contacts')->default(false);
            $table->boolean('exclude_selected')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6db6d08e4a01b1bc51f014f5');
        });
        Schema::create('tl_business_recipients_business_recipients__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_business_recipients_business_recipients')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_35a0258263fd39436697');
            $table->index('account_id', 'ix_3a5684e505f7826282ea82d0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_recipients_business_recipients__users');
        Schema::dropIfExists('tl_business_recipients_business_recipients');
        Schema::dropIfExists('tl_business_recipients');
    }
};
