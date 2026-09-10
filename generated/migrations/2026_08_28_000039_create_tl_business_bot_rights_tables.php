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
        Schema::create('tl_business_bot_rights_business_bot_rights', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('reply')->default(false);
            $table->boolean('read_messages')->default(false);
            $table->boolean('delete_sent_messages')->default(false);
            $table->boolean('delete_received_messages')->default(false);
            $table->boolean('edit_name')->default(false);
            $table->boolean('edit_bio')->default(false);
            $table->boolean('edit_profile_photo')->default(false);
            $table->boolean('edit_username')->default(false);
            $table->boolean('view_gifts')->default(false);
            $table->boolean('sell_gifts')->default(false);
            $table->boolean('change_gift_settings')->default(false);
            $table->boolean('transfer_and_upgrade_gifts')->default(false);
            $table->boolean('transfer_stars')->default(false);
            $table->boolean('manage_stories')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_622115bcd9a9d1eaf3eca626');
            $table->index('account_id', 'ix_747ea32cd044be94f1757154');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_bot_rights_business_bot_rights');
    }
};
