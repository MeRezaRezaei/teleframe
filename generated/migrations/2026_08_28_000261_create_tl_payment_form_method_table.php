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
        Schema::create('tl_payment_form_method', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_62a5fe03c7b794e96fceaef5');
            $table->index('account_id', 'ix_a5601867668b143e0000b57b');
        });
        Schema::create('tl_payment_form_method_payment_form_method', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payment_form_method')->cascadeOnDelete();
            $table->text('url');
            $table->text('title');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0cbf6a08caab6f81c21752e0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payment_form_method_payment_form_method');
        Schema::dropIfExists('tl_payment_form_method');
    }
};
