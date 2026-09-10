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
        Schema::create('tl_contacts_contacts_contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('saved_count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_592dd01986cc596c5adfc0fe');
            $table->index('account_id', 'ix_fc4948e15e187a0af0b6ef93');
        });
        Schema::create('tl_contacts_contacts_contacts__contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_contacts_contacts', 'id', 'fk_51dec9116383b58a40b520ce')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5f0575acfb65453ded3f');
            $table->index('account_id', 'ix_88909f3f54f6e1bab0016283');
        });
        Schema::create('tl_contacts_contacts_contacts__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_contacts_contacts', 'id', 'fk_47039c7d4d6887afd35857d5')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_57d9da3e3210581e41de');
            $table->index('account_id', 'ix_406c7992afb15dd1aea8b56e');
        });
        Schema::create('tl_contacts_contacts_contacts_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a7f20aec96eebe6113ff7482');
            $table->index('account_id', 'ix_53e44e9039c3ee009b4841a8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_contacts_contacts_not_modified');
        Schema::dropIfExists('tl_contacts_contacts_contacts__users');
        Schema::dropIfExists('tl_contacts_contacts_contacts__contacts');
        Schema::dropIfExists('tl_contacts_contacts_contacts');
    }
};
