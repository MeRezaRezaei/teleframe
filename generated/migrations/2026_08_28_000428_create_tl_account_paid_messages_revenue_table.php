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
        Schema::create('tl_account_paid_messages_revenue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_00593b7352d71999d647c055');
            $table->index('account_id', 'ix_71e829bdd8f2bbcc5ce75a78');
        });
        Schema::create('tl_account_paid_messages_revenue_paid_messages_revenue', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_paid_messages_revenue')->cascadeOnDelete();
            $table->bigInteger('stars_amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9ac1dc40728cc8b16924f7ce');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_paid_messages_revenue_paid_messages_revenue');
        Schema::dropIfExists('tl_account_paid_messages_revenue');
    }
};
