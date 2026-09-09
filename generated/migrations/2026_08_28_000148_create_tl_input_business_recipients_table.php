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
        Schema::create('tl_input_business_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e466916520d39b08d01ab442');
            $table->index('account_id', 'ix_48d9778dca130a470bd079cc');
        });
        Schema::create('tl_input_business_recipients_input_business_recipients', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_business_recipients')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('existing_chats')->default(false);
            $table->boolean('new_chats')->default(false);
            $table->boolean('contacts')->default(false);
            $table->boolean('non_contacts')->default(false);
            $table->boolean('exclude_selected')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5c2101f8b4d07238fc84bdb3');
        });
        Schema::create('tl_input_business_recipients_input_business_r_f3ebd21efedf', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_business_recipients_input_business_recipients')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_88228eda060257171ccd');
            $table->index('account_id', 'ix_0a3764b8bc6bd1dd68caa3f3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_business_recipients_input_business_r_f3ebd21efedf');
        Schema::dropIfExists('tl_input_business_recipients_input_business_recipients');
        Schema::dropIfExists('tl_input_business_recipients');
    }
};
