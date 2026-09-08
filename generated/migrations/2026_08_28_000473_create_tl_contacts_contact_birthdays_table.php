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
        Schema::create('tl_contacts_contact_birthdays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_contacts_contact_birthdays_contact_birthdays', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contacts_contact_birthdays')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_contacts_contact_birthdays_contact_birthdays__contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_contact_birthdays_contact_birthdays')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_344540395c4692facf6d');
        });
        Schema::create('tl_contacts_contact_birthdays_contact_birthdays__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_contact_birthdays_contact_birthdays')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_3a98e68193f5addc4f27');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_contact_birthdays_contact_birthdays__users');
        Schema::dropIfExists('tl_contacts_contact_birthdays_contact_birthdays__contacts');
        Schema::dropIfExists('tl_contacts_contact_birthdays_contact_birthdays');
        Schema::dropIfExists('tl_contacts_contact_birthdays');
    }
};
