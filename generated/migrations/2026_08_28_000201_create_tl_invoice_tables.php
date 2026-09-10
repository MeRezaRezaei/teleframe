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
        Schema::create('tl_invoice_invoice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
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
            $table->text('currency')->nullable();
            $table->bigInteger('max_tip_amount')->nullable();
            $table->text('terms_url')->nullable();
            $table->integer('subscription_period')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2a3ba4618cb9c9c44a05f871');
            $table->index('account_id', 'ix_88b40cd2db1580a33e09bbb2');
        });
        Schema::create('tl_invoice_invoice__prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_2b845c1e6b48f2a342e942a2')->references('id')->on('tl_invoice_invoice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_675530608a8fad684882');
            $table->index('account_id', 'ix_161c1114ef549605d049473f');
        });
        Schema::create('tl_invoice_invoice__suggested_tip_amounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_156d2b30a423e8ec5112e431')->references('id')->on('tl_invoice_invoice')->cascadeOnDelete();
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
    }
};
