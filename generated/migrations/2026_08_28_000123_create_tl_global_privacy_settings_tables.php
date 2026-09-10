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
        Schema::create('tl_global_privacy_settings_global_privacy_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('archive_and_mute_new_noncontact_peers')->default(false);
            $table->boolean('keep_archived_unmuted')->default(false);
            $table->boolean('keep_archived_folders')->default(false);
            $table->boolean('hide_read_marks')->default(false);
            $table->boolean('new_noncontact_peers_require_premium')->default(false);
            $table->boolean('display_gifts_button')->default(false);
            $table->bigInteger('noncontact_peers_paid_stars')->nullable();
            $table->bigInteger('disallowed_gifts')->nullable();
            $table->index('disallowed_gifts', 'ix_4593efc618a49f7b94eca185');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1dcbed4572e9e6695cfc1ec5');
            $table->index('account_id', 'ix_a19b29df5d7659ef6fc02c84');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_global_privacy_settings_global_privacy_settings');
    }
};
