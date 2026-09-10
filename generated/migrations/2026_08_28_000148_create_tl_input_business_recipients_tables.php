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
        Schema::create('tl_input_business_recipients_input_business_recipients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('existing_chats')->default(false);
            $table->boolean('new_chats')->default(false);
            $table->boolean('contacts')->default(false);
            $table->boolean('non_contacts')->default(false);
            $table->boolean('exclude_selected')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_25c17b124601271d5152f9f3');
            $table->index('account_id', 'ix_5c2101f8b4d07238fc84bdb3');
        });
        Schema::create('tl_input_business_recipients_input_business_r_f3ebd21efedf', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_62137aa4d1f5aa74a381fefd')->references('id')->on('tl_input_business_recipients_input_business_recipients')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_88228eda060257171ccd');
            $table->index('account_id', 'ix_0a3764b8bc6bd1dd68caa3f3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_business_recipients_input_business_r_f3ebd21efedf');
        Schema::dropIfExists('tl_input_business_recipients_input_business_recipients');
    }
};
