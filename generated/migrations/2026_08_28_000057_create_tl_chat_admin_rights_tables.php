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
        Schema::create('tl_chat_admin_rights_chat_admin_rights', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('change_info')->default(false);
            $table->boolean('post_messages')->default(false);
            $table->boolean('edit_messages')->default(false);
            $table->boolean('delete_messages')->default(false);
            $table->boolean('ban_users')->default(false);
            $table->boolean('invite_users')->default(false);
            $table->boolean('pin_messages')->default(false);
            $table->boolean('add_admins')->default(false);
            $table->boolean('anonymous')->default(false);
            $table->boolean('manage_call')->default(false);
            $table->boolean('other')->default(false);
            $table->boolean('manage_topics')->default(false);
            $table->boolean('post_stories')->default(false);
            $table->boolean('edit_stories')->default(false);
            $table->boolean('delete_stories')->default(false);
            $table->boolean('manage_direct_messages')->default(false);
            $table->boolean('manage_ranks')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_febc6b4609579863990e6da2');
            $table->index('account_id', 'ix_e3b37d99760339ea5f4a713a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_admin_rights_chat_admin_rights');
    }
};
