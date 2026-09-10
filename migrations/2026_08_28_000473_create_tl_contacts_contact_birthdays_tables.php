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
        Schema::create('tl_contacts_contact_birthdays_contact_birthdays', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b686e3901a7c3972715eba5f');
            $table->index('account_id', 'ix_1c4884f5df8a88fe21388141');
        });
        Schema::create('tl_contacts_contact_birthdays_contact_birthdays__contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_contact_birthdays_contact_birthdays', 'id', 'fk_007866a6edee2161372878c5')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_344540395c4692facf6d');
            $table->index('account_id', 'ix_1c87dad4352b7ef7510bc596');
        });
        Schema::create('tl_contacts_contact_birthdays_contact_birthdays__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_contact_birthdays_contact_birthdays', 'id', 'fk_60b197ffd8d018f81b062010')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3a98e68193f5addc4f27');
            $table->index('account_id', 'ix_5cfe6711e6dc6fa4ba765206');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_contact_birthdays_contact_birthdays__users');
        Schema::dropIfExists('tl_contacts_contact_birthdays_contact_birthdays__contacts');
        Schema::dropIfExists('tl_contacts_contact_birthdays_contact_birthdays');
    }
};
