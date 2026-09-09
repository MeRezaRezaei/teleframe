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
        Schema::create('tl_invoice', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c434169063520b42e02a087b');
            $table->index('account_id', 'ix_7ea700eca2957d18d08f89bf');
        });
        Schema::create('tl_invoice_invoice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_invoice')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('test')->default(false);
            $table->boolean('name_requested')->default(false);
            $table->boolean('phone_requested')->default(false);
            $table->boolean('email_requested')->default(false);
            $table->boolean('shipping_address_requested')->default(false);
            $table->boolean('flexible')->default(false);
            $table->boolean('phone_to_provider')->default(false);
            $table->boolean('email_to_provider')->default(false);
            $table->boolean('recurring')->default(false);
            $table->text('currency');
            $table->bigInteger('max_tip_amount')->nullable();
            $table->text('terms_url')->nullable();
            $table->integer('subscription_period')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_88b40cd2db1580a33e09bbb2');
        });
        Schema::create('tl_invoice_invoice__prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_invoice_invoice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_675530608a8fad684882');
            $table->index('account_id', 'ix_161c1114ef549605d049473f');
        });
        Schema::create('tl_invoice_invoice__suggested_tip_amounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_invoice_invoice')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b82048fcfbc3c56284c6');
            $table->index('account_id', 'ix_bc494adeb59ca1555688915d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_invoice_invoice__suggested_tip_amounts');
        Schema::dropIfExists('tl_invoice_invoice__prices');
        Schema::dropIfExists('tl_invoice_invoice');
        Schema::dropIfExists('tl_invoice');
    }
};
