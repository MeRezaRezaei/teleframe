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
        Schema::create('tl_group_call_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_785ff787575e5995b84caad6');
            $table->index('account_id', 'ix_f8eb890d227559871f5191f7');
        });
        Schema::create('tl_group_call_message_group_call_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_group_call_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('from_admin')->default(false);
            $table->integer('tl_id');
            $table->bigInteger('from_id');
            $table->index('from_id', 'ix_89df86a9d3e1cb53f503dcfb');
            $table->integer('date');
            $table->uuid('message');
            $table->index('message', 'ix_828cd7b674f51e94f9a5287f');
            $table->bigInteger('paid_message_stars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_260bf31a35cc49f9576d2d69');
            $table->unique(['account_id', 'from_id', 'tl_id'], 'ux_954bc3b63a6868ae324e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_message_group_call_message');
        Schema::dropIfExists('tl_group_call_message');
    }
};
